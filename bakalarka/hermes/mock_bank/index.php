<?php
/**
 * Hermes Mock Bank -- simulácia Berlin Group PSD2 AIS API.
 *
 * Endpointy:
 *   GET  /oauth2/authorize  -- presmeruje na redirect_uri s code + state
 *   POST /oauth2/token      -- vráti fake access_token JSON
 *   GET  /v1/accounts       -- zoznam testovacích účtov
 *   GET  /v1/accounts/{id}/transactions -- transakcie generované z DB (unpaid faktúry)
 *
 * Spúšťa sa cez php -S 0.0.0.0:80 index.php v Docker kontajneri.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query  = [];
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

$MOCK_IBAN = getenv('MOCK_IBAN') ?: 'SK3112000000198742637541';
$MOCK_ACCOUNT_ID = 'ACC-MOCK-001';

// ── OAuth2: authorize ────────────────────────────────────────────────────────

if ($uri === '/oauth2/authorize' && $method === 'GET') {
    $redirectUri = $query['redirect_uri'] ?? '';
    $state       = $query['state'] ?? '';

    if ($redirectUri === '') {
        http_response_code(400);
        echo json_encode(['error' => 'missing redirect_uri']);
        exit;
    }

    $code = 'MOCK-AUTH-CODE-' . bin2hex(random_bytes(8));

    $sep = str_contains($redirectUri, '?') ? '&' : '?';
    $target = $redirectUri . $sep . http_build_query([
        'code'  => $code,
        'state' => $state,
    ]);

    header('Location: ' . $target, true, 302);
    exit;
}

// ── OAuth2: token ────────────────────────────────────────────────────────────

if ($uri === '/oauth2/token' && $method === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'access_token'  => 'mock-access-' . bin2hex(random_bytes(16)),
        'token_type'    => 'Bearer',
        'expires_in'    => 86400,
        'refresh_token' => 'mock-refresh-' . bin2hex(random_bytes(16)),
        'scope'         => 'AIS',
    ]);
    exit;
}

// ── AIS: accounts ────────────────────────────────────────────────────────────

if ($uri === '/v1/accounts' && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'accounts' => [
            [
                'resourceId' => $MOCK_ACCOUNT_ID,
                'iban'       => $MOCK_IBAN,
                'currency'   => 'EUR',
                'name'       => 'Hermes Mock Bežný účet',
                'cashAccountType' => 'CACC',
                'balances' => [
                    [
                        'balanceType' => 'closingBooked',
                        'balanceAmount' => ['amount' => '10000.00', 'currency' => 'EUR'],
                    ],
                ],
            ],
        ],
    ]);
    exit;
}

// ── AIS: transactions ────────────────────────────────────────────────────────

if (preg_match('#^/v1/accounts/[^/]+/transactions$#', $uri) && $method === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    $dateFrom = $query['dateFrom'] ?? date('Y-m-d', strtotime('-90 days'));
    $dateTo   = $query['dateTo']   ?? date('Y-m-d');

    $booked = [];

    $pdo = connectDb();
    if ($pdo) {
        $rows = $pdo->query("
            SELECT i.id, i.invoice_number, i.vs, i.currency,
                   COALESCE(SUM(
                       (it.unit_price_cents * it.number DIV 10)
                       + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
                   ), 0) AS total_cents
            FROM invoices i
            LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
            LEFT JOIN items it ON it.id = ii.item_id
            WHERE i.status IN ('unpaid','overdue')
            GROUP BY i.id
            ORDER BY i.due_date ASC, i.id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $cents = (int)$r['total_cents'];
            if ($cents < 1) continue;

            $vs = $r['vs'] ?? '';
            $invNum = $r['invoice_number'] ?? '';
            $ccy = $r['currency'] ?? 'EUR';

            $booked[] = [
                'transactionId' => 'MOCKBANK-TX-' . $r['id'] . '-' . date('Ymd'),
                'bookingDate'   => date('Y-m-d'),
                'valueDate'     => date('Y-m-d'),
                'creditDebitIndicator' => 'CRDT',
                'transactionAmount' => [
                    'amount'   => number_format($cents / 100, 2, '.', ''),
                    'currency' => $ccy,
                ],
                'creditorName' => 'Mock platca',
                'creditorAccount' => ['iban' => 'SK0000000000000000001'],
                'remittanceInformationUnstructured' => [
                    'Uhrada faktury ' . $invNum,
                    'VS ' . $vs,
                    'variabilny symbol ' . preg_replace('/\D/', '', $vs),
                ],
            ];
        }
    }

    echo json_encode([
        'account' => ['iban' => $MOCK_IBAN],
        'transactions' => [
            'booked'  => $booked,
            'pending' => [],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── Fallback: info ───────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode([
    'service' => 'Hermes Mock Bank API',
    'endpoints' => [
        'GET  /oauth2/authorize',
        'POST /oauth2/token',
        'GET  /v1/accounts',
        'GET  /v1/accounts/{id}/transactions',
    ],
    'request' => $method . ' ' . $uri,
]);
exit;

// ── DB helper ────────────────────────────────────────────────────────────────

function connectDb(): ?PDO
{
    $host = getenv('DB_HOST') ?: 'mariadb';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'hermes_db';
    $user = getenv('DB_USER') ?: 'hermes';
    $pass = getenv('DB_PASSWORD') ?: '';

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        error_log('Mock Bank DB connect failed: ' . $e->getMessage());
        return null;
    }
}
