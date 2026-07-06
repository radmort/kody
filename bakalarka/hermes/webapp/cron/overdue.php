<?php
/**
 * Modul: Cron – kontrola splatnosti faktúr
 *
 * Označenie nezaplatených faktúr po dátume splatnosti ako „overdue".
 * Spúšťa sa denne o 07:55 cez crontab.
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/helpers.php';

try { DB::ensureSchema(); } catch (Throwable) {}

$now      = date('Y-m-d');
$updated  = mark_overdue_unpaid_invoices(null);
$count    = count($updated);

if ($count === 0) {
    echo "[{$now}] SUMMARY job=overdue date={$now} invoices_marked_overdue=0\n";
    echo "[{$now}] overdue: nothing to update\n";
    exit(0);
}

foreach ($updated as $inv) {
    echo "[{$now}] marked overdue: #{$inv['invoice_number']} (id={$inv['id']})\n";
}

echo "[{$now}] SUMMARY job=overdue date={$now} invoices_marked_overdue={$count}\n";
echo "[{$now}] overdue: {$count} invoice(s) updated\n";
exit(0);
