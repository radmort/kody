<?php
/**
 * Modul: OAuth2 callback (banka)
 *
 * Spracovanie návratového presmerovania z banky po OAuth2 autorizácii.
 * Výmena kódu za tokeny, výber účtu a uloženie prepojenia do DB.
 */

require_once __DIR__ . '/../lib/SbasConfig.php';
require_once __DIR__ . '/../lib/BankTokenVault.php';
require_once __DIR__ . '/../lib/SbasHttp.php';
require_once __DIR__ . '/../lib/SbasOAuth.php';
require_once __DIR__ . '/../lib/BankPaymentSync.php';

$uid = (int)current_user_id();
if ($uid < 1) {
    redirect(url('login'));
}

$cfg = SbasConfig::load();
if (!$cfg->enabled()) {
    flash_error('Bankové AIS nie je v konfigurácii zapnuté (config/sbas.json → enabled).');
    redirect(url('bank'));
}

if (isset($_GET['error'])) {
    $desc = (string)($_GET['error_description'] ?? $_GET['error']);
    flash_error('Banka odmietla prístup: ' . $desc);
    redirect(url('bank'));
}

$code = (string)($_GET['code'] ?? '');
$state = (string)($_GET['state'] ?? '');
$sessState = (string)($_SESSION['sbas_oauth_state'] ?? '');
$pkce = isset($_SESSION['sbas_pkce_verifier']) ? (string)$_SESSION['sbas_pkce_verifier'] : null;

unset($_SESSION['sbas_oauth_state'], $_SESSION['sbas_pkce_verifier']);

if ($code === '' || $state === '' || $sessState === '' || !hash_equals($sessState, $state)) {
    flash_error('Neplatná alebo expirovaná OAuth odpoveď. Skúste znova prepojiť banku zo stránky Banka.');
    redirect(url('bank'));
}

if (!BankTokenVault::canEncrypt()) {
    flash_error('Chýba HERMES_BANK_TOKEN_KEY – tokeny nie je možné uložiť.');
    redirect(url('bank'));
}

try {
    $tok = SbasOAuth::exchangeCode($cfg, $code, $cfg->usePkce() ? $pkce : null);
    $access = $tok['access_token'];
    $refresh = $tok['refresh_token'] ?? null;
    $expiresIn = isset($tok['expires_in']) ? (int)$tok['expires_in'] : null;

    $accRes = SbasOAuth::fetchAccounts($cfg, $access);
    if (!$accRes['ok']) {
        throw new RuntimeException('Načítanie účtov zlyhalo HTTP ' . $accRes['status']);
    }

    $accounts = SbasOAuth::parseAccountsList($accRes['json']);
    if ($accounts === []) {
        throw new RuntimeException('Banka nevrátila žiadny účet (skontrolujte scope AIS).');
    }

    $urow = DB::one('SELECT iban FROM users WHERE id=?', [$uid]);
    $userIban = preg_replace('/\s+/', '', (string)($urow['iban'] ?? ''));

    $pick = null;
    foreach ($accounts as $a) {
        if ($userIban !== '' && ($a['iban'] ?? '') === $userIban) {
            $pick = $a;
            break;
        }
    }
    if ($pick === null) {
        foreach ($accounts as $a) {
            if (($a['currency'] ?? 'EUR') === 'EUR') {
                $pick = $a;
                break;
            }
        }
    }
    if ($pick === null) {
        $pick = $accounts[0];
    }

    $catalogPick = $_SESSION['sbas_catalog_bank_id'] ?? null;
    if (is_string($catalogPick) && $catalogPick !== '' && $catalogPick !== 'file'
        && SbasBanksCatalog::isValidBankId($catalogPick)) {
        $label = $catalogPick;
    } else {
        $label = $cfg->providerLabel();
    }
    unset($_SESSION['sbas_catalog_bank_id']);
    $expAt = null;
    if ($expiresIn !== null && $expiresIn > 0) {
        $expAt = date('Y-m-d H:i:s', time() + $expiresIn - 30);
    }

    $encA = BankTokenVault::encrypt($access);
    $encR = $refresh !== null ? BankTokenVault::encrypt($refresh) : null;

    $existing = DB::one(
        'SELECT id FROM bank_connections WHERE user_id=? AND provider_label=?',
        [$uid, $label]
    );

    if ($existing) {
        DB::exec(
            'UPDATE bank_connections SET
                account_resource_id=?, account_iban_display=?, access_token_enc=?, refresh_token_enc=?,
                token_expires_at=?, status=?, last_error=NULL, updated_at=NOW()
             WHERE id=?',
            [
                $pick['resourceId'],
                $pick['iban'],
                $encA,
                $encR,
                $expAt,
                'active',
                (int)$existing['id'],
            ]
        );
    } else {
        DB::exec(
            'INSERT INTO bank_connections
                (user_id, provider_label, account_resource_id, account_iban_display,
                 access_token_enc, refresh_token_enc, token_expires_at, status)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                $uid,
                $label,
                $pick['resourceId'],
                $pick['iban'],
                $encA,
                $encR,
                $expAt,
                'active',
            ]
        );
    }

    flash_success('Banka bola prepojená. Účet AIS: ' . ($pick['iban'] ?? $pick['resourceId']));
} catch (Throwable $e) {
    unset($_SESSION['sbas_catalog_bank_id']);
    flash_error('OAuth / AIS: ' . $e->getMessage());
}

redirect(url('bank'));
