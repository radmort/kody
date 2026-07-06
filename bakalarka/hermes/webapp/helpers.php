<?php
/**
 * Modul: Pomocné funkcie (helpers)
 *
 * Zdieľané utility pre celú aplikáciu: flash správy, CSRF ochrana,
 * formátovanie peňazí a DPH, stavové badges, výpočet súm faktúry,
 * generovanie čísla faktúry a VS, audit log, PDF cache.
 *
 * Hlavné funkcie:
 *   calc_totals()                    – výpočet základu, DPH a celkovej sumy
 *   suggest_next_invoice_number()    – ďalšie číslo faktúry v rade VF-ROK-PORADIE
 *   mark_overdue_unpaid_invoices()   – automatické overdue po splatnosti
 *   invoice_pdf_ensure_cached()      – generovanie PDF cez billing_reminder
 *   audit_log()                      – zápis do audit_log tabuľky
 */

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Flash správy
// ═══════════════════════════════════════════════════════════════════

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_success(string $msg): void { flash('success', $msg); }
function flash_error(string $msg): void   { flash('danger',  $msg); }
function flash_info(string $msg): void    { flash('info',    $msg); }

function render_flashes(): string
{
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $type = h($f['type']);
        $msg  = h($f['msg']);
        $html .= <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
            {$msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        HTML;
    }
    $_SESSION['flash'] = [];
    return $html;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Bezpečnosť (HTML escape, CSRF, redirect)
// ═══════════════════════════════════════════════════════════════════

/** HTML-escape shorthand */
function h(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Mäkké znaky → ASCII pre pole „meno účtu“ v QR (bankové čítačky). */
function ascii_fold(string $s): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t === false) {
        $t = '';
    }
    $t = preg_replace('/[^a-zA-Z0-9 .\-\/]/', '', $t) ?? '';

    return trim($t) !== '' ? trim($t) : 'User';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (($_POST['_csrf'] ?? '') !== csrf_token()) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Formátovanie peňazí a DPH
// ═══════════════════════════════════════════════════════════════════

/** Format cents to "1 234,56 EUR" */
function money(int $cents, string $currency = 'EUR'): string
{
    $eur  = $cents / 100;
    $neg  = $eur < 0;
    $abs  = abs($eur);
    $fmt  = number_format($abs, 2, ',', ' ');
    return ($neg ? '-' : '') . $fmt . ' ' . $currency;
}

/** Parse EUR string to cents: "19.99" → 1999 */
function parse_cents(string $s): int
{
    return (int)round((float)str_replace(',', '.', $s) * 100);
}

/** basis points to percent string: 2000 → "20" */
function bp_to_pct(int $bp): string
{
    return rtrim(rtrim(number_format($bp / 100, 2, '.', ''), '0'), '.');
}

/** "20" → 2000 bp */
function pct_to_bp(string $pct): int
{
    return (int)round((float)$pct * 100);
}

/** Predvolená sadzba DPH pri nových položkách (percentá). */
function default_item_vat_pct(): int
{
    return 23;
}

/** number column (tenths) to display: 10 → "1,0" */
function qty_display(int $n): string
{
    return number_format($n / 10, 1, ',', ' ');
}

/** "1.5" → 15 */
function parse_qty(string $s): int
{
    return (int)round((float)str_replace(',', '.', $s) * 10);
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Stavové odznaky a jednotky
// ═══════════════════════════════════════════════════════════════════

function status_badge(string $status): string
{
    $map = [
        'draft'     => ['secondary', 'Koncept'],
        'unpaid'    => ['warning',   'Nezaplatená'],
        'paid'      => ['success',   'Zaplatená'],
        'overdue'   => ['danger',    'Po splatnosti'],
        'cancelled' => ['dark',      'Stornovaná'],
    ];
    [$color, $label] = $map[$status] ?? ['secondary', $status];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function unit_label(int $unit): string
{
    return $unit === 1 ? 'hod' : 'ks';
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Výpočet súm faktúry
// ═══════════════════════════════════════════════════════════════════

function calc_totals(array $items): array
{
    $subtotal = 0;
    $tax      = 0;
    // Výpočet: cena_v_centoch × množstvo_v_desatinách / 10 = netto v centoch
    // DPH: netto × sadzba_v_basis_points / 10000 (napr. 2000 bp = 20%)
    foreach ($items as $it) {
        $line_net = (int)$it['unit_price_cents'] * (int)$it['number'];  // cents × tenths
        $line_net = intdiv($line_net, 10);                               // back to cents
        $line_vat = intdiv($line_net * (int)$it['vat_bp'], 10000);
        $subtotal += $line_net;
        $tax      += $line_vat;
    }
    return [
        'subtotal' => $subtotal,
        'tax'      => $tax,
        'total'    => $subtotal + $tax,
    ];
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Dátumové pomocníky
// ═══════════════════════════════════════════════════════════════════

function today(): string  { return date('Y-m-d'); }
function plus_days(int $d): string { return date('Y-m-d', strtotime("+{$d} days")); }

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Číslovanie faktúr a variabilný symbol
// ═══════════════════════════════════════════════════════════════════

/**
 * Ďalšie číslo faktúry v rade používateľa: VF-{ROK}-{PORADIE}.
 */
function suggest_next_invoice_number(int $userId): string
{
    $year = (int)date('Y');
    $maxSeq = 0;
    $like = 'VF-' . $year . '-%';
    foreach (DB::all(
        'SELECT invoice_number FROM invoices WHERE user_id=? AND invoice_number LIKE ?',
        [$userId, $like]
    ) as $row) {
        $num = (string)($row['invoice_number'] ?? '');
        if (preg_match('/^VF-' . $year . '-(\d{4})$/i', $num, $m)) {
            $maxSeq = max($maxSeq, (int)$m[1]);
        }
    }

    return sprintf('VF-%d-%04d', $year, $maxSeq + 1);
}

/** Rovnaké ako suggest_next_invoice_number (client_id sa kvôli formulárom nevyužíva). */
function suggest_invoice_number_for_client(int $clientId, int $userId): string
{
    return suggest_next_invoice_number($userId);
}

/**
 * Predvolený variabilný symbol z čísla faktúry: VF-2026-0001 → VF20260001 (bez pomlčiek, vhodné pre QR).
 * Starý formát R2026-0001 → 2026-0001 (kompatibilita).
 */
function vs_suggest_from_invoice_number(string $invoiceNumber): string
{
    $n = trim($invoiceNumber);
    if (preg_match('/^VF-(\d{4})-(\d{4})$/i', $n, $m)) {
        return 'VF' . $m[1] . $m[2];
    }
    if (preg_match('/^[A-Za-z](\d{4}-\d+)$/', $n, $m)) {
        return $m[1];
    }

    return $n;
}

/**
 * Predvolený VS pri vytvorení faktúry (manuálne / šablóna / cron) – z čísla faktúry.
 */
function invoice_default_vs(string $invoiceNumber): string
{
    return vs_suggest_from_invoice_number($invoiceNumber);
}

/**
 * Či je povolené natrvalo vymazať faktúru z DB (inak len storno cez stav „cancelled“).
 */
function invoice_delete_blocked_reason(array $invoice, int $paymentCount = 0): ?string
{
    $st = (string)($invoice['status'] ?? '');
    if ($st === 'paid') {
        return 'Zaplatenú faktúru nie je možné vymazať. Použite storno (stav „Stornované“).';
    }
    if ($st === 'cancelled') {
        return 'Stornovanú faktúru nie je možné vymazať z účtovných dôvodov.';
    }
    if ($paymentCount > 0) {
        return 'Faktúra má záznam o platbe – vymazať ju nie je možné.';
    }
    $issue = (string)($invoice['issue_date'] ?? '');
    if (strlen($issue) < 10) {
        return 'Chýba platný dátum vystavenia.';
    }
    // Účtovné pravidlo: faktúru staršiu ako 1 mesiac možno len stornovať
    $limit = date('Y-m-d', strtotime('-1 month'));
    if ($issue < $limit) {
        return 'Faktúru staršiu ako jeden mesiac možno len stornovať (stav „Stornované“), nie vymazať.';
    }

    return null;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: URL builder
// ═══════════════════════════════════════════════════════════════════

function url(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], $params);
    return '/?' . http_build_query($params);
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Audit log
// ═══════════════════════════════════════════════════════════════════

function audit_log(string $entity, int $entity_id, string $action, array $detail = []): void
{
    try {
        DB::exec(
            "INSERT INTO audit_log(entity, entity_id, action, detail) VALUES(?,?,?,?)",
            [$entity, $entity_id, $action, !empty($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null]
        );
    } catch (Throwable) {}
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Automatické overdue (zdieľané s cron a UI)
// ═══════════════════════════════════════════════════════════════════

/**
 * Nezaplatené faktúry so splatnosťou pred dneškom → stav overdue.
 *
 * @param int|null $onlyInvoiceId Len táto faktúra (napr. po create/update v UI); null = všetky zodpovedajúce.
 * @return list<array{id:int, invoice_number:string}> Práve označené faktúry (pre log v crone)
 */
function mark_overdue_unpaid_invoices(?int $onlyInvoiceId = null): array
{
    $now = date('Y-m-d');
    $sql  = '
        SELECT id, invoice_number FROM invoices
        WHERE status = \'unpaid\'
          AND due_date < ?
    ';
    $params = [$now];
    if ($onlyInvoiceId !== null) {
        $sql .= ' AND id = ? ';
        $params[] = $onlyInvoiceId;
    }
    $to_mark = DB::all($sql, $params);
    $updated = [];
    foreach ($to_mark as $inv) {
        $id = (int)$inv['id'];
        DB::exec("UPDATE invoices SET status='overdue' WHERE id=?", [$id]);
        audit_log('invoice', $id, 'auto_overdue', ['date' => $now]);
        $updated[] = ['id' => $id, 'invoice_number' => (string)$inv['invoice_number']];
    }

    return $updated;
}

/**
 * Má sa pri odoslaní e-mailu z faktúry použiť text upomienky (nie „vystavili sme faktúru“)?
 * Logika zodpovedá billing_reminder v main.cpp: po splatnosti okamžite, inak po REMINDER_DAYS_AFTER_ISSUE dňoch od vystavenia.
 */
function invoice_email_use_payment_reminder_body(string $issueDateRaw, string $dueDateRaw): bool
{
    $issueDateYmd = substr($issueDateRaw, 0, 10);
    $dueDateYmd   = substr($dueDateRaw, 0, 10);
    $today = new DateTimeImmutable('today');
    $due   = DateTimeImmutable::createFromFormat('Y-m-d', $dueDateYmd);
    $issue = DateTimeImmutable::createFromFormat('Y-m-d', $issueDateYmd);
    if (!$due || !$issue) {
        return false;
    }
    if ($today > $due) {
        return true;
    }
    // Po uplynutí grace period od vystavenia sa posiela upomienka namiesto oznámenia
    $afterGrace = $issue->modify('+' . REMINDER_DAYS_AFTER_ISSUE . ' days');

    return $today >= $afterGrace;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: PDF cache
// ═══════════════════════════════════════════════════════════════════

function pdf_cache_path(int $invoice_id): string
{
    return '/tmp/hermes_pdf_cache/invoice_' . $invoice_id . '.pdf';
}

function pdf_cache_clear(int $invoice_id): void
{
    @unlink(pdf_cache_path($invoice_id));
}

/**
 * Ensure a cached PDF exists (billing_reminder --pdf= when missing).
 *
 * @return array{ok:true, path:string}|array{ok:false, message:string}
 */
function invoice_pdf_ensure_cached(int $invoice_id): array
{
    $cache_file = pdf_cache_path($invoice_id);
    if (is_file($cache_file) && filesize($cache_file) > 0) {
        return ['ok' => true, 'path' => $cache_file];
    }

    $binary = '/usr/local/bin/billing_reminder';
    $config = getenv('HERMES_CONFIG') ?: '/etc/hermes/config.json';

    if (!is_executable($binary)) {
        return ['ok' => false, 'message' => 'Binárka billing_reminder nie je dostupná (napr. mimo Docker).'];
    }

    $cmd = 'cd /etc/hermes && timeout 120 '
        . escapeshellcmd($binary) . ' '
        . escapeshellarg($config)
        . ' --pdf=' . (int)$invoice_id
        . ' 2>&1';
    $raw = shell_exec($cmd) ?? '';

    $output = '';
    foreach (array_reverse(explode("\n", $raw)) as $line) {
        $trimmed = trim($line);
        if (preg_match('/((?:[A-Za-z]:\\\\[^\r\n]+|\/[^\r\n]+)\.pdf)$/', $trimmed, $m)) {
            $output = $m[1];
            break;
        }
    }

    if ($output === '' || !is_file($output)) {
        return ['ok' => false, 'message' => 'PDF sa nepodarilo vygenerovať: ' . substr(trim($raw), 0, 400)];
    }

    $cache_dir = dirname($cache_file);
    @mkdir($cache_dir, 0755, true);
    if (!copy($output, $cache_file)) {
        return ['ok' => false, 'message' => 'Nepodarilo sa skopírovať PDF do cache.'];
    }
    @unlink($output);
    @chmod($cache_file, 0664);

    return ['ok' => true, 'path' => $cache_file];
}
