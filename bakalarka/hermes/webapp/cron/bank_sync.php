<?php
/**
 * Modul: Cron – synchronizácia bankových platieb
 *
 * Stiahnutie transakcií cez AIS (SBAS/PSD2) a automatické označenie
 * faktúr ako zaplatených podľa VS a sumy. Odporúčané: každých 15–60 min.
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers.php';
require_once dirname(__DIR__) . '/lib/SbasConfig.php';
require_once dirname(__DIR__) . '/lib/BankTokenVault.php';
require_once dirname(__DIR__) . '/lib/SbasHttp.php';
require_once dirname(__DIR__) . '/lib/SbasOAuth.php';
require_once dirname(__DIR__) . '/lib/BankPaymentSync.php';

try { DB::ensureSchema(); } catch (Throwable) {}

$d = date('Y-m-d');
$t = date('c');

$cfg = SbasConfig::load();
if (!$cfg->enabled() && !$cfg->mockEnabled()) {
    echo "[{$t}] SUMMARY job=bank_sync date={$d} status=skipped reason=sbas_disabled\n";
    exit(0);
}

$n = (int)DB::scalar("SELECT COUNT(*) FROM bank_connections WHERE status='active'");
if ($n < 1) {
    echo "[{$t}] SUMMARY job=bank_sync date={$d} status=skipped reason=no_active_connections\n";
    exit(0);
}

$res = BankPaymentSync::syncAllConnections();
$marked = (int)$res['marked_total'];
$errN = count($res['errors']);
echo "[{$t}] SUMMARY job=bank_sync date={$d} active_connections={$n} invoices_marked_paid={$marked} sync_errors={$errN}\n";
if ($errN > 0) {
    echo "[{$t}] bank_sync detail: " . implode('; ', $res['errors']) . "\n";
}
exit(0);
