#!/usr/bin/env php
<?php
/**
 * cron/monthly.php – automatický generátor mesačných faktúr Hermes – generátor faktúr zo šablón
 *
 * Spustenie cez cron (napr. denne o 08:00):
 *   0 8 * * * php /var/www/html/cron/monthly.php >> /var/log/hermes/cron_php.log 2>&1
 *
 * Generuje faktúry pre všetky aktívne šablóny, ktorých send_day zodpovedá dnešnému dňu
 * a pre ktoré ešte nebola v tomto kalendárnom mesiaci vygenerovaná faktúra.
 * Odošle e-mailové upozornenie zákazníkovi.
 */

// Spustenie aplikácie (rovnaká konfigurácia / DB / pomocné funkcie ako webová aplikácia)
Translated with DeepL.com (free version)
define('CLI_MODE', true);
chdir(dirname(__DIR__));
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../mailer.php';

function log_line(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// ── Ensure schema ─────────────────────────────────────────────────────────────
try {
    DB::ensureSchema();
} catch (Throwable $e) {
    log_line("Schema check failed: " . $e->getMessage());
}

$today_day = (int)date('j'); // day of month, no leading zero
$date_ymd  = date('Y-m-d');
log_line("=== Hermes Monthly Invoice Job – Day {$today_day} ({$date_ymd}) ===");

// ── Find templates scheduled for today ────────────────────────────────────────
$templates = DB::all("
    SELECT t.*, c.name AS client_name, c.email AS client_email
    FROM invoice_templates t
    JOIN clients c ON c.id = t.client_id
    WHERE t.active    = 1
      AND t.send_day  = ?
      AND t.user_id   = c.user_id
    ORDER BY t.id ASC
", [$today_day]);

$tpl_count = count($templates);
log_line("Templates scheduled for today (send_day={$today_day}): {$tpl_count}");

if (empty($templates)) {
    log_line('SUMMARY job=monthly_templates '
        . "date={$date_ymd} day={$today_day} "
        . 'templates_today=0 skipped_already_month=0 invoices_created=0 emails_sent_ok=0 emails_failed=0 invoice_create_errors=0');
    log_line('Nothing to do.');
    exit(0);
}

$ok_count = 0;
$created_count = 0;
$skipped_month = 0;
$email_failed = 0;
$invoice_create_errors = 0;

foreach ($templates as $tpl) {
    $tpl_id = (int)$tpl['id'];
    log_line("Template [{$tpl_id}] \"{$tpl['name']}\" → {$tpl['client_name']}");

    // ── Idempotency: skip if already generated this calendar month ─────────────
    $already = DB::scalar("
        SELECT COUNT(*) FROM invoices
        WHERE template_id = ?
          AND YEAR(issue_date)  = YEAR(CURDATE())
          AND MONTH(issue_date) = MONTH(CURDATE())
    ", [$tpl_id]);

    if ($already > 0) {
        log_line("  → Already generated this month, skipping.");
        $skipped_month++;
        continue;
    }

    // ── Generate invoice ───────────────────────────────────────────────────────
    $tpl_uid    = (int)($tpl['user_id'] ?? 1);
    $inv_number = suggest_invoice_number_for_client((int)$tpl['client_id'], $tpl_uid);
    $issue_date = today();
    $due_date   = plus_days((int)$tpl['due_days']);
    $vs = invoice_default_vs($inv_number);

    DB::begin();
    $inv_id = null;
    try {
        DB::exec("
            INSERT INTO invoices(client_id,user_id,invoice_number,status,currency,issue_date,due_date,vs,template_id)
            VALUES(?,?,?,?,?,?,?,?,?)
        ", [$tpl['client_id'], $tpl_uid, $inv_number, 'unpaid', $tpl['currency'],
           $issue_date, $due_date, $vs, $tpl_id]);
        $inv_id = (int)DB::lastId();

        $tpl_items = DB::all("SELECT * FROM template_items WHERE template_id=? ORDER BY position", [$tpl_id]);

        if (empty($tpl_items)) {
            throw new RuntimeException("Template has no items defined.");
        }

        foreach ($tpl_items as $ti) {
            DB::exec("
                INSERT INTO items(user_id,name,description,unit,unit_price_cents,vat_bp,number)
                VALUES(?,?,?,?,?,?,?)
            ", [$tpl_uid, $ti['name'], $ti['description'], $ti['unit'],
               $ti['unit_price_cents'], $ti['vat_bp'], $ti['number']]);
            $item_id = (int)DB::lastId();

            DB::exec("INSERT INTO invoice_items(invoice_id,item_id,position) VALUES(?,?,?)",
                [$inv_id, $item_id, $ti['position']]);
        }

        DB::commit();
        mark_overdue_unpaid_invoices($inv_id);
        $created_count++;
        log_line("  ✓ Invoice created: {$inv_number} (id={$inv_id})");

    } catch (Throwable $e) {
        DB::rollback();
        log_line("  ✗ Invoice creation failed: " . $e->getMessage());
        $invoice_create_errors++;
        continue;
    }

    // ── Calculate total for email ──────────────────────────────────────────────
    $items_for_total = DB::all("
        SELECT it.unit_price_cents, it.number, it.vat_bp
        FROM invoice_items ii
        JOIN items it ON it.id = ii.item_id
        WHERE ii.invoice_id = ?
    ", [$inv_id]);
    $totals = calc_totals($items_for_total);

    // PDF len pre túto faktúru; mail cez PHP (nie dávkový C++ cron, ktorý môže spadnúť na inej faktúre).
    $mailer = Mailer::forUserId($tpl_uid);
    $pdf = invoice_pdf_ensure_cached($inv_id);
    if ($pdf['ok']) {
        $attach = 'Faktura_' . preg_replace('/[^-a-zA-Z0-9_.]/', '_', $inv_number) . '.pdf';
        $sent   = $mailer->sendInvoiceIssuedNotice(
            $tpl['client_email'],
            $tpl['client_name'],
            $inv_number,
            $due_date,
            $tpl['currency'],
            $totals['total'],
            $pdf['path'],
            $attach
        );
        $error_msg = $sent ? '' : 'SMTP zlyhal';
    } else {
        $sent = $mailer->sendInvoiceIssuedNotice(
            $tpl['client_email'],
            $tpl['client_name'],
            $inv_number,
            $due_date,
            $tpl['currency'],
            $totals['total']
        );
        $error_msg = $sent
            ? ('Odoslané bez PDF: ' . $pdf['message'])
            : ($pdf['message'] . ' (SMTP zlyhal)');
    }

    $ok_label = $sent && $pdf['ok'] ? '✓ PDF+email' : ($sent ? '✓ email (bez PDF alebo len text)' : '✗ FAILED');
    log_line("  {$ok_label} → {$tpl['client_email']}");
    if (!$sent) log_line("    Error: {$error_msg}");

    // ── Log reminder ───────────────────────────────────────────────────────────
    DB::exec("
        INSERT INTO reminder_log(invoice_id, client_id, sent_at, success, error_message)
        VALUES(?, ?, NOW(), ?, ?)
    ", [$inv_id, $tpl['client_id'], $sent ? 1 : 0, $error_msg]);

    if ($sent) {
        $ok_count++;
    } else {
        $email_failed++;
    }
}

$total_errors = $invoice_create_errors + $email_failed;
log_line(
    'SUMMARY job=monthly_templates '
    . "date={$date_ymd} day={$today_day} "
    . "templates_today={$tpl_count} skipped_already_month={$skipped_month} "
    . "invoices_created={$created_count} emails_sent_ok={$ok_count} "
    . "emails_failed={$email_failed} invoice_create_errors={$invoice_create_errors}"
);
log_line("=== Done: emails_sent_ok={$ok_count}, total_errors={$total_errors} ===");
exit($total_errors > 0 ? 1 : 0);
