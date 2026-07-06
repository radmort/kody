<?php
/**
 * Modul: Synchronizácia bankových platieb
 *
 * Parsovanie transakcií (Berlin Group / SBAS JSON) a automatické
 * párovanie s faktúrami podľa variabilného symbolu a sumy.
 *
 * Hlavné metódy:
 *   syncConnection()              – sync jedného bankového prepojenia
 *   syncAllConnections()          – cron: všetky aktívne prepojenia
 *   applyMatches()                – párovanie transakcií s faktúrami
 *   generateAutoMockTransactions() – mock transakcie pre testovanie
 */
class BankPaymentSync
{
    public static function amountStringToCents(string $amount, string $currency): int
    {
        $amount = str_replace(',', '.', trim($amount));
        if ($amount === '') {
            return 0;
        }
        if (!is_numeric($amount)) {
            return 0;
        }
        $neg = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');

        return (int)round((float)$amount * 100) * ($neg ? -1 : 1);
    }

    /**
     * @param array<string,mixed> $row
     * @return array{
     *   external_id:string,
     *   booking_date:string,
     *   amount_cents:int,
     *   currency:string,
     *   is_credit:bool,
     *   remittance_text:string
     * }|null
     */
    public static function normalizeTransactionRow(array $row): ?array
    {
        $ext = $row['transactionId'] ?? $row['entryReference'] ?? $row['endToEndId'] ?? null;
        if ($ext === null || $ext === '') {
            $ext = sha1(json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        $ext = (string)$ext;
        if (strlen($ext) > 250) {
            $ext = substr($ext, 0, 250);
        }

        $booking = $row['bookingDate'] ?? $row['valueDate'] ?? $row['bookingDateTime'] ?? null;
        if ($booking === null) {
            return null;
        }
        $bookingStr = (string)$booking;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $bookingStr, $m)) {
            $bookingStr = $m[1];
        } else {
            $ts = strtotime($bookingStr);
            $bookingStr = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
        }

        $amtBlock = $row['transactionAmount'] ?? $row['amount'] ?? null;
        if (is_array($amtBlock)) {
            $amtStr = (string)($amtBlock['amount'] ?? $amtBlock['value'] ?? '0');
            $ccy = strtoupper((string)($amtBlock['currency'] ?? 'EUR'));
        } else {
            $amtStr = '0';
            $ccy = 'EUR';
        }
        $cents = self::amountStringToCents($amtStr, $ccy);

        $cd = strtoupper((string)($row['creditDebitIndicator'] ?? ''));
        $isCredit = ($cd === 'CRDT' || $cd === 'CREDIT');
        if ($cd === '' && $cents > 0) {
            $isCredit = true;
        }
        if ($cd === 'DBIT' || $cd === 'DEBIT') {
            $isCredit = false;
        }

        $remParts = [];
        if (!empty($row['remittanceInformationUnstructured'])) {
            foreach ((array)$row['remittanceInformationUnstructured'] as $u) {
                $remParts[] = (string)$u;
            }
        }
        $rsi = $row['remittanceInformationStructured'] ?? null;
        if (is_array($rsi)) {
            $ref = $rsi['creditorReferenceInformation']['reference'] ?? $rsi['reference'] ?? null;
            if ($ref !== null) {
                $remParts[] = (string)$ref;
            }
        }
        if (!empty($row['additionalInformation'])) {
            $remParts[] = (string)$row['additionalInformation'];
        }
        $remText = mb_strtolower(implode(' ', $remParts), 'UTF-8');

        return [
            'external_id'     => $ext,
            'booking_date'    => $bookingStr,
            'amount_cents'    => abs($cents),
            'currency'        => $ccy,
            'is_credit'       => $isCredit,
            'remittance_text' => $remText,
        ];
    }

    /**
     * Číslice z VS / čísla faktúry pre párovanie v texte platby.
     * Stratégia: extrahuje viacero „odtlačkov" (číslice VS, kompaktný tvar,
     * číslo faktúry, číslice z čísla) a hľadá ich v poznámke platby.
     */
    public static function vsFingerprints(?string $vs, string $invoiceNumber): array
    {
        $out = [];
        $vs = trim((string)$vs);
        if ($vs !== '') {
            $d = preg_replace('/\D/', '', $vs) ?? '';
            if ($d !== '') {
                $out[] = $d;
            }
            $compact = preg_replace('/\s+/', '', $vs) ?? '';
            $compact = mb_strtolower($compact, 'UTF-8');
            if ($compact !== '' && strlen($compact) >= 4) {
                $out[] = $compact;
            }
        }
        $inv = preg_replace('/\s+/', '', $invoiceNumber) ?? '';
        $inv = mb_strtolower($inv, 'UTF-8');
        if ($inv !== '') {
            $out[] = $inv;
            $d2 = preg_replace('/\D/', '', $invoiceNumber) ?? '';
            if ($d2 !== '' && $d2 !== ($out[0] ?? '')) {
                $out[] = $d2;
            }
        }

        return array_values(array_unique(array_filter($out)));
    }

    /** Celková suma faktúry v centoch (netto + DPH, celočíselná aritmetika). */
    public static function invoiceTotalCents(int $invoiceId): int
    {
        // netto = cena × množstvo / 10; DPH = netto × bp / 10000
        return (int)DB::scalar("
            SELECT COALESCE(SUM(
                (it.unit_price_cents * it.number DIV 10)
                + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
            ), 0)
            FROM invoice_items ii
            JOIN items it ON it.id = ii.item_id
            WHERE ii.invoice_id = ?", [$invoiceId]);
    }

    /**
     * @param list<array<string,mixed>> $normalizedCredits už len príchozí pohyby
     * @return int počet novo označených faktúr
     */
    public static function applyMatches(int $bankConnectionId, int $userId, array $normalizedCredits): int
    {
        $unpaid = DB::all("
            SELECT id, vs, invoice_number, currency, status
            FROM invoices
            WHERE user_id = ? AND status IN ('unpaid','overdue')
            ORDER BY due_date ASC, id ASC
        ", [$userId]);

        $marked = 0;
        $unpaidPool = $unpaid;

        foreach ($normalizedCredits as $tx) {
            if (empty($tx['is_credit'])) {
                continue;
            }
            $ext = $tx['external_id'];
            $exists = DB::scalar(
                'SELECT COUNT(*) FROM bank_imported_transactions WHERE bank_connection_id=? AND external_id=?',
                [$bankConnectionId, $ext]
            );
            if ($exists) {
                continue;
            }

            $txCents = (int)$tx['amount_cents'];
            $txCcy = strtoupper((string)$tx['currency']);
            // Spojený text poznámky platby (VS, štruktúrovaná referencia…) – už lower-case
            $hay = $tx['remittance_text'];

            $matchedId = null;
            foreach ($unpaidPool as $inv) {
                if (strtoupper((string)$inv['currency']) !== $txCcy) {
                    continue;
                }
                $total = self::invoiceTotalCents((int)$inv['id']);
                if ($total < 1) {
                    continue;
                }
                // Platba musí pokryť faktúru (1 cent tolerancia kvôli zaokrúhleniu v API)
                if ($txCents + 1 < $total) {
                    continue;
                }

                // Musí sa v texte platby objaviť aspoň jeden „odtlačok“ VS alebo čísla faktúry
                $fps = self::vsFingerprints($inv['vs'] ?? '', (string)$inv['invoice_number']);
                $hit = false;
                foreach ($fps as $fp) {
                    if ($fp === '') {
                        continue;
                    }
                    if (str_contains($hay, mb_strtolower($fp, 'UTF-8'))) {
                        $hit = true;
                        break;
                    }
                }
                if (!$hit) {
                    continue;
                }

                $matchedId = (int)$inv['id'];
                break;
            }

            $detail = [
                'remittance' => $hay,
                'amount'     => $txCents,
                'currency'   => $txCcy,
            ];

            if ($matchedId === null) {
                DB::exec(
                    'INSERT INTO bank_imported_transactions
                        (bank_connection_id, external_id, invoice_id, booked_date, amount_cents, currency, match_detail)
                     VALUES (?,?,NULL,?,?,?,?)',
                    [
                        $bankConnectionId,
                        $ext,
                        $tx['booking_date'],
                        $txCents,
                        $txCcy,
                        json_encode($detail, JSON_UNESCAPED_UNICODE),
                    ]
                );

                continue;
            }

            DB::begin();
            try {
                DB::exec(
                    'INSERT INTO bank_imported_transactions
                        (bank_connection_id, external_id, invoice_id, booked_date, amount_cents, currency, match_detail)
                     VALUES (?,?,?,?,?,?,?)',
                    [
                        $bankConnectionId,
                        $ext,
                        $matchedId,
                        $tx['booking_date'],
                        $txCents,
                        $txCcy,
                        json_encode($detail + ['invoice_id' => $matchedId], JSON_UNESCAPED_UNICODE),
                    ]
                );

                DB::exec('DELETE FROM payments WHERE invoice_id=?', [$matchedId]);
                DB::exec(
                    'INSERT INTO payments(invoice_id,amount_cents,currency,paid_at,method,reference,notes,source,external_ref)
                     VALUES(?,?,?,?,?,?,?,?,?)',
                    [
                        $matchedId,
                        $txCents,
                        $txCcy,
                        $tx['booking_date'],
                        'bank',
                        'AIS/' . $ext,
                        'Automaticky zo banky (SBAS/AIS)',
                        'bank_ais',
                        $ext,
                    ]
                );
                DB::exec("UPDATE invoices SET status='paid' WHERE id=? AND user_id=?", [$matchedId, $userId]);
                pdf_cache_clear($matchedId);
                audit_log('invoice', $matchedId, 'paid_bank_ais', ['external_id' => $ext, 'connection_id' => $bankConnectionId]);
                DB::commit();
                $marked++;
                $unpaidPool = array_values(array_filter(
                    $unpaidPool,
                    static fn (array $x): bool => (int)$x['id'] !== $matchedId
                ));
            } catch (Throwable $e) {
                DB::rollback();
                throw $e;
            }
        }

        return $marked;
    }

    /**
     * Obnoví access token ak treba, stiahne transakcie, spustí párovanie.
     *
     * @return array{ok:bool,message:string,marked:int}
     */
    public static function syncConnection(int $connectionId): array
    {
        $row = DB::one('SELECT * FROM bank_connections WHERE id=? AND status=?', [$connectionId, 'active']);
        if (!$row) {
            return ['ok' => false, 'message' => 'Spojenie neexistuje alebo je odpojené.', 'marked' => 0];
        }

        $cfg = SbasConfig::loadForBankConnection((string)($row['provider_label'] ?? ''));

        $isMockConn = (($row['account_resource_id'] ?? '') === 'mock'
            || str_ends_with((string)($row['provider_label'] ?? ''), '_mock'));
        if ($isMockConn) {
            if (!$cfg->mockEnabled()) {
                return ['ok' => false, 'message' => 'Mock účet vyžaduje mock.enabled v sbas.json alebo SBAS_USE_MOCK=1.', 'marked' => 0];
            }

            return self::syncMock((int)$row['user_id'], $connectionId, $cfg);
        }

        if (!BankTokenVault::canEncrypt()) {
            return ['ok' => false, 'message' => 'Chýba HERMES_BANK_TOKEN_KEY.', 'marked' => 0];
        }

        try {
            $access = BankTokenVault::decrypt($row['access_token_enc']);
            $refresh = $row['refresh_token_enc'] ? BankTokenVault::decrypt($row['refresh_token_enc']) : null;

            $exp = $row['token_expires_at'] ? strtotime((string)$row['token_expires_at']) : 0;
            if ($refresh && $exp > 0 && $exp < time() + 120) {
                $tok = SbasOAuth::refresh($cfg, $refresh);
                $access = $tok['access_token'];
                $refresh = $tok['refresh_token'] ?? $refresh;
                self::persistTokens((int)$row['id'], $access, $refresh, $tok['expires_in'] ?? null);
            }

            $accId = (string)$row['account_resource_id'];
            if ($accId === '') {
                return ['ok' => false, 'message' => 'Chýba vybraný účet (account_resource_id).', 'marked' => 0];
            }

            $dateTo = date('Y-m-d');
            $fromTs = strtotime('-120 days');
            if (!empty($row['last_sync_at'])) {
                $fromTs = min($fromTs, strtotime((string)$row['last_sync_at'] . ' UTC') - 3 * 86400);
            }
            $dateFrom = date('Y-m-d', $fromTs);

            $res = SbasOAuth::fetchTransactions($cfg, $access, $accId, $dateFrom, $dateTo);
            if (!$res['ok']) {
                $msg = 'AIS HTTP ' . $res['status'] . ' ' . substr($res['body'], 0, 300);
                DB::exec('UPDATE bank_connections SET status=?, last_error=?, last_sync_at=NOW() WHERE id=?', ['error', $msg, $connectionId]);

                return ['ok' => false, 'message' => $msg, 'marked' => 0];
            }

            $booked = SbasOAuth::parseBookedTransactions($res['json']);
            $credits = [];
            foreach ($booked as $br) {
                $n = self::normalizeTransactionRow($br);
                if ($n !== null && $n['is_credit']) {
                    $credits[] = $n;
                }
            }

            $marked = self::applyMatches($connectionId, (int)$row['user_id'], $credits);

            DB::exec(
                'UPDATE bank_connections SET status=?, last_error=NULL, last_sync_at=NOW() WHERE id=?',
                ['active', $connectionId]
            );

            return ['ok' => true, 'message' => 'Synchronizované.', 'marked' => $marked];
        } catch (Throwable $e) {
            $em = $e->getMessage();
            DB::exec('UPDATE bank_connections SET status=?, last_error=? WHERE id=?', ['error', $em, $connectionId]);

            return ['ok' => false, 'message' => $em, 'marked' => 0];
        }
    }

    /** @param array{access_token?:string,refresh_token?:string,expires_in?:int} $tok */
    public static function persistTokens(int $connectionId, string $access, ?string $refresh, ?int $expiresIn): void
    {
        $expAt = null;
        if ($expiresIn !== null && $expiresIn > 0) {
            $expAt = date('Y-m-d H:i:s', time() + $expiresIn - 30);
        }
        DB::exec(
            'UPDATE bank_connections SET access_token_enc=?, refresh_token_enc=?, token_expires_at=?, status=?, last_error=NULL WHERE id=?',
            [
                BankTokenVault::encrypt($access),
                $refresh !== null ? BankTokenVault::encrypt($refresh) : null,
                $expAt,
                'active',
                $connectionId,
            ]
        );
    }

    /**
     * Vygeneruje mock CRDT transakcie pre všetky unpaid/overdue faktúry používateľa.
     * Každá transakcia má správny VS, sumu a menu aby prešla cez applyMatches().
     *
     * @param int        $userId
     * @param int[]|null $onlyInvoiceIds  Ak nie je null, generuje len pre dané ID faktúr.
     * @return list<array<string,mixed>>  Berlin Group-like riadky
     */
    public static function generateAutoMockTransactions(int $userId, ?array $onlyInvoiceIds = null): array
    {
        $sql = "SELECT id, invoice_number, vs, currency FROM invoices
                WHERE user_id = ? AND status IN ('unpaid','overdue')";
        $params = [$userId];

        if ($onlyInvoiceIds !== null && $onlyInvoiceIds !== []) {
            $ph = implode(',', array_fill(0, count($onlyInvoiceIds), '?'));
            $sql .= " AND id IN ({$ph})";
            foreach ($onlyInvoiceIds as $iid) {
                $params[] = (int)$iid;
            }
        }

        $invoices = DB::all($sql, $params);
        $txs = [];
        foreach ($invoices as $inv) {
            $total = self::invoiceTotalCents((int)$inv['id']);
            if ($total < 1) {
                continue;
            }
            $txs[] = [
                'transactionId' => 'AUTO-MOCK-' . $inv['id'] . '-' . date('Ymd'),
                'bookingDate'   => date('Y-m-d'),
                'creditDebitIndicator' => 'CRDT',
                'transactionAmount' => [
                    'amount'   => number_format($total / 100, 2, '.', ''),
                    'currency' => $inv['currency'] ?? 'EUR',
                ],
                'remittanceInformationUnstructured' => [
                    'VS ' . ($inv['vs'] ?? ''),
                    $inv['invoice_number'],
                ],
            ];
        }

        return $txs;
    }

    /**
     * Vytvorí jednu custom mock transakciu (pre testovanie edge cases).
     *
     * @return array<string,mixed> Berlin Group-like riadok
     */
    public static function buildCustomMockTransaction(string $amount, string $vs, string $currency = 'EUR'): array
    {
        return [
            'transactionId' => 'CUSTOM-MOCK-' . bin2hex(random_bytes(6)),
            'bookingDate'   => date('Y-m-d'),
            'creditDebitIndicator' => 'CRDT',
            'transactionAmount' => [
                'amount'   => $amount,
                'currency' => strtoupper($currency),
            ],
            'remittanceInformationUnstructured' => [
                'VS ' . $vs,
                $vs,
            ],
        ];
    }

    /**
     * @return array{ok:bool,message:string,marked:int}
     */
    private static function syncMock(int $userId, int $connectionId, SbasConfig $cfg): array
    {
        $credits = [];

        foreach ($cfg->mockTransactions() as $row) {
            if (!is_array($row)) {
                continue;
            }
            $n = self::normalizeTransactionRow($row);
            if ($n !== null && $n['is_credit']) {
                $credits[] = $n;
            }
        }

        foreach (self::generateAutoMockTransactions($userId) as $row) {
            $n = self::normalizeTransactionRow($row);
            if ($n !== null && $n['is_credit']) {
                $credits[] = $n;
            }
        }

        $marked = self::applyMatches($connectionId, $userId, $credits);
        DB::exec('UPDATE bank_connections SET last_sync_at=NOW(), last_error=NULL, status=? WHERE id=?', ['active', $connectionId]);

        return ['ok' => true, 'message' => 'Mock synchronizácia.', 'marked' => $marked];
    }

    /**
     * Selektívna mock synchronizácia – len pre vybrané faktúry.
     *
     * @param int[] $invoiceIds
     * @return array{ok:bool,message:string,marked:int}
     */
    public static function syncMockSelected(int $userId, int $connectionId, array $invoiceIds): array
    {
        $credits = [];
        foreach (self::generateAutoMockTransactions($userId, $invoiceIds) as $row) {
            $n = self::normalizeTransactionRow($row);
            if ($n !== null && $n['is_credit']) {
                $credits[] = $n;
            }
        }
        $marked = self::applyMatches($connectionId, $userId, $credits);
        DB::exec('UPDATE bank_connections SET last_sync_at=NOW(), last_error=NULL, status=? WHERE id=?', ['active', $connectionId]);

        return ['ok' => true, 'message' => 'Mock synchronizácia (vybrané).', 'marked' => $marked];
    }

    /**
     * Spustí custom mock transakciu cez matching engine.
     *
     * @return array{ok:bool,message:string,marked:int}
     */
    public static function syncMockCustom(int $userId, int $connectionId, string $amount, string $vs, string $currency = 'EUR'): array
    {
        $raw = self::buildCustomMockTransaction($amount, $vs, $currency);
        $n = self::normalizeTransactionRow($raw);
        $credits = ($n !== null && $n['is_credit']) ? [$n] : [];
        $marked = self::applyMatches($connectionId, $userId, $credits);
        DB::exec('UPDATE bank_connections SET last_sync_at=NOW(), last_error=NULL, status=? WHERE id=?', ['active', $connectionId]);

        return ['ok' => true, 'message' => 'Custom mock transakcia spracovaná.', 'marked' => $marked];
    }

    /** Cron: všetky aktívne spojenia */
    public static function syncAllConnections(): array
    {
        $ids = DB::all("SELECT id FROM bank_connections WHERE status='active'");
        $sum = 0;
        $errors = [];
        foreach ($ids as $r) {
            $res = self::syncConnection((int)$r['id']);
            $sum += $res['marked'];
            if (!$res['ok']) {
                $errors[] = 'conn#' . $r['id'] . ': ' . $res['message'];
            }
        }

        return ['marked_total' => $sum, 'errors' => $errors];
    }
}
