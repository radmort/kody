<?php
/**
 * Modul: Správa faktúr
 *
 * CRUD faktúr, zmena stavu, záznam platby, odoslanie e-mailu s PDF,
 * hromadná zmena stavu, synchronizácia s bankou (AIS) a stiahnutie PDF.
 */

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$uid    = (int)current_user_id();

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: AJAX – návrh čísla faktúry
// ═══════════════════════════════════════════════════════════════════

if (isset($_GET['ajax']) && $_GET['ajax'] === 'suggest_number') {
    header('Content-Type: application/json; charset=utf-8');
    $cid = (int)($_GET['client_id'] ?? 0);
    if ($cid < 1) {
        echo json_encode(['invoice_number' => '', 'vs' => '']);
        exit;
    }
    $num = suggest_invoice_number_for_client($cid, $uid);
    echo json_encode([
        'invoice_number' => $num,
        'vs'             => vs_suggest_from_invoice_number($num),
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Stiahnutie PDF
// ═══════════════════════════════════════════════════════════════════
if ($action === 'download' && $id) {
    if (!DB::scalar('SELECT COUNT(*) FROM invoices WHERE id=? AND user_id=?', [$id, $uid])) {
        flash_error('Faktúra neexistuje.');
        redirect(url('invoices'));
    }
    $cache_file = pdf_cache_path($id);

    if (!file_exists($cache_file)) {
        $gen = invoice_pdf_ensure_cached($id);
        if (!$gen['ok']) {
            $msg = htmlspecialchars($gen['message'], ENT_QUOTES, 'UTF-8');
            flash_error("PDF: {$msg}");
            redirect(url('invoices', ['action' => 'view', 'id' => $id]));
        }
    }

    $inv_row  = DB::one("SELECT invoice_number FROM invoices WHERE id=?", [$id]);
    $filename = 'Faktura_' . ($inv_row['invoice_number'] ?? $id) . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($cache_file));
    header('Cache-Control: private, no-cache');
    readfile($cache_file);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: POST handlery (CRUD, stav, platba, e-mail, bulk, sync)
// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';

    // ── Create / Update ──────────────────────────────────────────────────────
    if ($a === 'create' || $a === 'update') {
        $inv = [
            'client_id'      => (int)$_POST['client_id'],
            'invoice_number' => trim($_POST['invoice_number'] ?? ''),
            'status'         => $_POST['status'] ?? 'unpaid',
            'currency'       => $_POST['currency'] ?? 'EUR',
            'issue_date'     => $_POST['issue_date'] ?? today(),
            'due_date'       => $_POST['due_date']   ?? plus_days(14),
            'vs'             => trim($_POST['vs'] ?? '') ?: null,
        ];
        $line_names  = $_POST['item_name']  ?? [];
        $line_descs  = $_POST['item_desc']  ?? [];
        $line_units  = $_POST['item_unit']  ?? [];
        $line_prices = $_POST['item_price'] ?? [];
        $line_qtys   = $_POST['item_qty']   ?? [];
        $line_vats   = $_POST['item_vat']   ?? [];

        $hasItemName = false;
        $rowsPriceWithoutName = 0;
        foreach ($line_names as $pos => $name) {
            $n = trim((string)$name);
            $price_c = parse_cents($line_prices[$pos] ?? '0');
            $qty_raw = parse_qty($line_qtys[$pos] ?? '1');
            if ($n !== '') {
                $hasItemName = true;
            } elseif ($price_c > 0 || $qty_raw !== 10) {
                $rowsPriceWithoutName++;
            }
        }

        if (empty($inv['invoice_number']) || !$inv['client_id']) {
            flash_error('Číslo faktúry a zákazník sú povinné.');
        } elseif (!$hasItemName) {
            $msg = 'Faktúra musí mať aspoň jednu položku s vyplneným názvom.';
            if ($rowsPriceWithoutName > 0) {
                $msg .= ' Pri položke bez názvu sa cena ani množstvo neuloží – dopíšte názov.';
            }
            flash_error($msg);
        } else {
            DB::begin();
            try {
                if ($a === 'create') {
                    if (!DB::scalar('SELECT COUNT(*) FROM clients WHERE id=? AND user_id=?', [$inv['client_id'], $uid])) {
                        flash_error('Neplatný zákazník.');
                        redirect(url('invoices'));
                    }
                    DB::exec("INSERT INTO invoices(client_id,user_id,invoice_number,status,currency,issue_date,due_date,vs)
                              VALUES(?,?,?,?,?,?,?,?)",
                        [$inv['client_id'], $uid, $inv['invoice_number'], $inv['status'],
                         $inv['currency'], $inv['issue_date'], $inv['due_date'], $inv['vs']]);
                    $inv_id = (int)DB::lastId();
                    audit_log('invoice', $inv_id, 'created', ['number' => $inv['invoice_number']]);
                } else {
                    $inv_id = (int)$_POST['id'];
                    if (!DB::scalar('SELECT COUNT(*) FROM invoices WHERE id=? AND user_id=?', [$inv_id, $uid])) {
                        flash_error('Faktúra neexistuje.');
                        redirect(url('invoices'));
                    }
                    if (!DB::scalar('SELECT COUNT(*) FROM clients WHERE id=? AND user_id=?', [$inv['client_id'], $uid])) {
                        flash_error('Neplatný zákazník.');
                        redirect(url('invoices'));
                    }
                    DB::exec("UPDATE invoices SET client_id=?,invoice_number=?,status=?,currency=?,
                              issue_date=?,due_date=?,vs=? WHERE id=? AND user_id=?",
                        [$inv['client_id'],$inv['invoice_number'],$inv['status'],
                         $inv['currency'],$inv['issue_date'],$inv['due_date'],$inv['vs'],$inv_id,$uid]);
                    $old_items = DB::all("SELECT item_id FROM invoice_items WHERE invoice_id=?", [$inv_id]);
                    DB::exec("DELETE FROM invoice_items WHERE invoice_id=?", [$inv_id]);
                    foreach ($old_items as $oi) {
                        DB::exec("DELETE FROM items WHERE id=?", [$oi['item_id']]);
                    }
                    pdf_cache_clear($inv_id);
                    audit_log('invoice', $inv_id, 'updated', ['number' => $inv['invoice_number']]);
                }
                foreach ($line_names as $pos => $name) {
                    if (trim($name) === '') continue;
                    $price_cents = parse_cents($line_prices[$pos] ?? '0');
                    $qty         = parse_qty($line_qtys[$pos]  ?? '1');
                    $vat_bp      = pct_to_bp($line_vats[$pos]  ?? '0');
                    $unit        = (int)($line_units[$pos]     ?? 0);
                    DB::exec("INSERT INTO items(user_id,name,description,unit,unit_price_cents,vat_bp,number)
                              VALUES(?,?,?,?,?,?,?)",
                        [$uid, trim($name), trim($line_descs[$pos] ?? ''), $unit, $price_cents, $vat_bp, $qty]);
                    $item_id = (int)DB::lastId();
                    DB::exec("INSERT INTO invoice_items(invoice_id,item_id,position) VALUES(?,?,?)",
                        [$inv_id, $item_id, $pos + 1]);
                }
                DB::commit();
                mark_overdue_unpaid_invoices($inv_id);
                flash_success($a === 'create' ? 'Faktúra vytvorená.' : 'Faktúra aktualizovaná.');
                if ($rowsPriceWithoutName > 0) {
                    flash_info(
                        'Pozor: ' . $rowsPriceWithoutName . ' riadok(ov) malo cenu alebo množstvo bez názvu položky – tie sa neuložili.'
                    );
                }
                redirect(url('invoices', ['action'=>'view','id'=>$inv_id]));
            } catch (Throwable $e) {
                DB::rollback();
                flash_error('Chyba: ' . $e->getMessage());
            }
        }
    }

    // ── Set status ───────────────────────────────────────────────────────────
    if ($a === 'set_status') {
        $new_status = $_POST['status'] ?? '';
        $inv_id     = (int)$_POST['id'];
        $allowed    = ['draft','unpaid','paid','overdue','cancelled'];
        if (in_array($new_status, $allowed)) {
            DB::exec("UPDATE invoices SET status=? WHERE id=? AND user_id=?", [$new_status, $inv_id, $uid]);
            audit_log('invoice', $inv_id, 'status_changed', ['status' => $new_status]);
            if ($new_status === 'unpaid') {
                mark_overdue_unpaid_invoices($inv_id);
            }
            flash_success('Stav faktúry zmenený.');
        }
        redirect(url('invoices', ['action'=>'view','id'=>$inv_id]));
    }

    // ── Record payment ───────────────────────────────────────────────────────
    if ($a === 'record_payment') {
        $inv_id      = (int)$_POST['id'];
        $paid_at     = $_POST['paid_at'] ?? today();
        $method      = $_POST['method'] ?? 'bank';
        $reference   = trim($_POST['reference'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');
        $allowed_methods = ['bank','cash','card','other'];

        if (!in_array($method, $allowed_methods)) $method = 'bank';

        DB::begin();
        try {
            // Get invoice total for payment record
            $total = (int)DB::scalar("
                SELECT COALESCE(SUM(
                    (it.unit_price_cents * it.number DIV 10)
                    + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
                ), 0)
                FROM invoice_items ii
                JOIN items it ON it.id = ii.item_id
                WHERE ii.invoice_id = ?", [$inv_id]);

            $inv_row = DB::one("SELECT currency FROM invoices WHERE id=? AND user_id=?", [$inv_id, $uid]);
            $currency = $inv_row['currency'] ?? 'EUR';

            // Remove existing payment if re-recording
            DB::exec("DELETE FROM payments WHERE invoice_id=?", [$inv_id]);

            DB::exec("INSERT INTO payments(invoice_id,amount_cents,currency,paid_at,method,reference,notes)
                      VALUES(?,?,?,?,?,?,?)",
                [$inv_id, $total, $currency, $paid_at, $method, $reference ?: null, $notes ?: null]);

            DB::exec("UPDATE invoices SET status='paid' WHERE id=? AND user_id=?", [$inv_id, $uid]);
            pdf_cache_clear($inv_id);
            audit_log('invoice', $inv_id, 'paid', ['method' => $method, 'paid_at' => $paid_at]);
            DB::commit();
            flash_success('Platba zaznamenaná. Faktúra označená ako zaplatená.');
        } catch (Throwable $e) {
            DB::rollback();
            flash_error('Chyba pri zápise platby: ' . $e->getMessage());
        }
        redirect(url('invoices', ['action'=>'view','id'=>$inv_id]));
    }

    // ── Send email ───────────────────────────────────────────────────────────
    if ($a === 'send_email') {
        $inv_id = (int)$_POST['id'];

        $inv_row = DB::one("
            SELECT i.*, c.name AS client_name, c.email AS client_email
            FROM invoices i JOIN clients c ON c.id = i.client_id
            WHERE i.id = ? AND i.user_id = ?", [$inv_id, $uid]);

        if ($inv_row) {
            $pdf = invoice_pdf_ensure_cached($inv_id);
            if (!$pdf['ok']) {
                flash_error('E-mail neodoslaný – PDF: ' . $pdf['message']);
                redirect(url('invoices', ['action' => 'view', 'id' => $inv_id]));
            }

            $items_for_total = DB::all("
                SELECT it.unit_price_cents, it.number, it.vat_bp
                FROM invoice_items ii JOIN items it ON it.id = ii.item_id
                WHERE ii.invoice_id = ?", [$inv_id]);
            $totals  = calc_totals($items_for_total);
            $attach  = 'Faktura_' . preg_replace('/[^-a-zA-Z0-9_.]/', '_', $inv_row['invoice_number']) . '.pdf';
            $mailer  = Mailer::forUserId($uid);
            $useReminder = invoice_email_use_payment_reminder_body(
                (string)$inv_row['issue_date'],
                (string)$inv_row['due_date']
            );
            if ($useReminder) {
                $sent = $mailer->sendPaymentReminder(
                    $inv_row['client_email'],
                    $inv_row['client_name'],
                    $inv_row['invoice_number'],
                    $inv_row['due_date'],
                    $inv_row['currency'],
                    $totals['total'],
                    $pdf['path'],
                    $attach
                );
            } else {
                $sent = $mailer->sendInvoiceIssuedNotice(
                    $inv_row['client_email'],
                    $inv_row['client_name'],
                    $inv_row['invoice_number'],
                    $inv_row['due_date'],
                    $inv_row['currency'],
                    $totals['total'],
                    $pdf['path'],
                    $attach
                );
            }

            $errDetail = $sent ? null : 'SMTP alebo príloha zlyhala';

            $queueSubject = $useReminder
                ? ('Upomienka platby – faktúra ' . $inv_row['invoice_number'])
                : ('Faktúra ' . $inv_row['invoice_number']);

            // Log to email_queue
            DB::exec("INSERT INTO email_queue(invoice_id,recipient,subject,status,attempt_count,sent_at,error_message)
                      VALUES(?,?,?,?,1,?,?)",
                [$inv_id, $inv_row['client_email'],
                 $queueSubject,
                 $sent ? 'sent' : 'failed',
                 $sent ? date('Y-m-d H:i:s') : null,
                 $errDetail]);

            // Log to reminder_log
            DB::exec("INSERT INTO reminder_log(invoice_id,client_id,sent_at,success,error_message)
                      VALUES(?,?,NOW(),?,?)",
                [$inv_id, $inv_row['client_id'], $sent ? 1 : 0, $errDetail]);

            audit_log('invoice', $inv_id, 'email_sent', ['to' => $inv_row['client_email'], 'success' => $sent, 'pdf' => true]);
            $sent
                ? flash_success('E-mail s PDF prílohou odoslaný na ' . $inv_row['client_email'])
                : flash_error('E-mail sa nepodarilo odoslať. Skontroluj SMTP nastavenia.');
        }
        redirect(url('invoices', ['action'=>'view','id'=>$inv_id]));
    }

    // ── Bulk set status ─────────────────────────────────────────────────────
    if ($a === 'bulk_set_status') {
        $new_status = $_POST['status'] ?? '';
        $allowed = ['draft', 'unpaid', 'paid', 'overdue', 'cancelled'];
        $ids = array_map('intval', (array)($_POST['invoice_ids'] ?? []));

        if (!in_array($new_status, $allowed, true)) {
            flash_error('Neplatný stav.');
        } elseif ($ids === []) {
            flash_error('Nevybrali ste žiadne faktúry.');
        } else {
            $changed = 0;
            foreach ($ids as $iid) {
                if ($iid < 1) continue;
                $inv_check = DB::one('SELECT id, status FROM invoices WHERE id=? AND user_id=?', [$iid, $uid]);
                if (!$inv_check || $inv_check['status'] === $new_status) continue;

                if ($new_status === 'paid' && $inv_check['status'] !== 'paid') {
                    $total = (int)DB::scalar("
                        SELECT COALESCE(SUM(
                            (it.unit_price_cents * it.number DIV 10)
                            + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
                        ), 0)
                        FROM invoice_items ii JOIN items it ON it.id = ii.item_id
                        WHERE ii.invoice_id = ?", [$iid]);
                    $ccy = DB::scalar('SELECT currency FROM invoices WHERE id=?', [$iid]) ?: 'EUR';
                    DB::exec('DELETE FROM payments WHERE invoice_id=?', [$iid]);
                    DB::exec("INSERT INTO payments(invoice_id,amount_cents,currency,paid_at,method,notes,source)
                              VALUES(?,?,?,?,?,?,?)",
                        [$iid, $total, $ccy, today(), 'other', 'Hromadná zmena stavu', 'manual_bulk']);
                }

                DB::exec("UPDATE invoices SET status=? WHERE id=? AND user_id=?", [$new_status, $iid, $uid]);
                audit_log('invoice', $iid, 'bulk_status_changed', ['status' => $new_status]);
                if ($new_status === 'unpaid') {
                    mark_overdue_unpaid_invoices($iid);
                }
                pdf_cache_clear($iid);
                $changed++;
            }
            flash_success("Stav zmenený pre {$changed} faktúr.");
        }

        $rs = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['ret_status'] ?? 'all')));
        $rq = (string)($_POST['ret_sort'] ?? 'id');
        if (!in_array($rs, ['all', 'unpaid', 'overdue', 'paid', 'draft', 'cancelled'], true)) $rs = 'all';
        if (!in_array($rq, ['id', 'client', 'due'], true)) $rq = 'id';
        redirect(url('invoices', ['status' => $rs, 'sort' => $rq]));
    }

    // ── Bank AIS sync (všetky aktívne prepojenia používateľa) ─────────────────
    if ($a === 'bank_sync_from_invoice' || $a === 'bank_sync_from_list') {
        require_once __DIR__ . '/../lib/SbasConfig.php';
        require_once __DIR__ . '/../lib/BankTokenVault.php';
        require_once __DIR__ . '/../lib/SbasHttp.php';
        require_once __DIR__ . '/../lib/SbasOAuth.php';
        require_once __DIR__ . '/../lib/BankPaymentSync.php';

        $fromList = ($a === 'bank_sync_from_list');
        $inv_id   = (int)($_POST['id'] ?? 0);

        $bankListQuery = ['status' => 'all', 'sort' => 'id'];
        if ($fromList) {
            $rs = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['ret_status'] ?? 'all')));
            $rq = (string)($_POST['ret_sort'] ?? 'id');
            if (!in_array($rs, ['all', 'unpaid', 'overdue', 'paid', 'draft', 'cancelled'], true)) {
                $rs = 'all';
            }
            if (!in_array($rq, ['id', 'client', 'due'], true)) {
                $rq = 'id';
            }
            $bankListQuery = ['status' => $rs, 'sort' => $rq];
        }

        if (!$fromList) {
            if (!DB::scalar('SELECT COUNT(*) FROM invoices WHERE id=? AND user_id=?', [$inv_id, $uid])) {
                flash_error('Faktúra neexistuje.');
                redirect(url('invoices'));
            }
        }

        $cfg = SbasConfig::load();
        if (!$cfg->enabled() && !$cfg->mockEnabled()) {
            flash_info('Bankové AIS nie je zapnuté (config/sbas.json).');
            redirect($fromList ? url('invoices', $bankListQuery) : url('invoices', ['action' => 'view', 'id' => $inv_id]));
        }
        if (!BankTokenVault::canEncrypt()) {
            flash_error('Nastavte HERMES_BANK_TOKEN_KEY.');
            redirect($fromList ? url('invoices', $bankListQuery) : url('invoices', ['action' => 'view', 'id' => $inv_id]));
        }

        $rows = DB::all("SELECT id FROM bank_connections WHERE user_id=? AND status='active'", [$uid]);
        if ($rows === []) {
            flash_info('Najprv prepojte banku v menu Banka.');
            redirect(url('bank'));
        }

        $totalMarked = 0;
        $lastErr = '';
        $ok = true;
        foreach ($rows as $row) {
            $r = BankPaymentSync::syncConnection((int)$row['id']);
            $totalMarked += (int)$r['marked'];
            if (!$r['ok']) {
                $ok = false;
                $lastErr = $r['message'];
            }
        }

        if ($ok) {
            flash_success('Banka: synchronizované. Novo označených ako zaplatené: ' . $totalMarked . '.');
        } else {
            flash_error('Banka: ' . $lastErr);
        }

        redirect($fromList ? url('invoices', $bankListQuery) : url('invoices', ['action' => 'view', 'id' => $inv_id]));
    }
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Vymazanie faktúry
// ═══════════════════════════════════════════════════════════════════
if ($action === 'delete' && $id) {
    $inv_del = DB::one('SELECT id, status, issue_date, invoice_number FROM invoices WHERE id=? AND user_id=?', [$id, $uid]);
    if (!$inv_del) {
        flash_error('Faktúra neexistuje.');
        redirect(url('invoices'));
    }
    $pay_n = (int)DB::scalar('SELECT COUNT(*) FROM payments WHERE invoice_id=?', [$id]);
    $why = invoice_delete_blocked_reason($inv_del, $pay_n);
    if ($why !== null) {
        flash_error($why);
        redirect(url('invoices', ['action' => 'view', 'id' => $id]));
    }
    $old_items = DB::all("SELECT item_id FROM invoice_items WHERE invoice_id=?", [$id]);
    DB::begin();
    DB::exec("DELETE FROM invoice_items WHERE invoice_id=?", [$id]);
    foreach ($old_items as $oi) {
        DB::exec("DELETE FROM items WHERE id=? AND user_id=?", [$oi['item_id'], $uid]);
    }
    DB::exec("DELETE FROM invoices WHERE id=? AND user_id=?", [$id, $uid]);
    DB::commit();
    pdf_cache_clear($id);
    audit_log('invoice', $id, 'deleted');
    flash_success('Faktúra vymazaná.');
    redirect(url('invoices'));
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Načítanie dát pre zobrazenie
// ═══════════════════════════════════════════════════════════════════
$clients = DB::all("SELECT id, name FROM clients WHERE user_id=? ORDER BY name", [$uid]);

$bank_sync_ui_show = false;
$bank_sync_conn_n = 0;

if (in_array($action, ['view','edit']) && $id) {
    $invoice = DB::one("SELECT i.*, c.name AS client_name, c.email AS client_email
                        FROM invoices i LEFT JOIN clients c ON c.id=i.client_id
                        WHERE i.id=? AND i.user_id=?", [$id, $uid]);
    if (!$invoice) { flash_error('Faktúra neexistuje.'); redirect(url('invoices')); }
    $inv_items = DB::all("
        SELECT it.*, ii.position
        FROM invoice_items ii
        JOIN items it ON it.id = ii.item_id
        WHERE ii.invoice_id=?
        ORDER BY ii.position ASC", [$id]);
    // Payment record if exists
    $payment = DB::one("SELECT * FROM payments WHERE invoice_id=?", [$id]);
    $payment_n_view = (int)DB::scalar('SELECT COUNT(*) FROM payments WHERE invoice_id=?', [$id]);
    $can_hard_delete = invoice_delete_blocked_reason($invoice, $payment_n_view) === null;
    // Last email sent
    $last_email = DB::one("SELECT * FROM email_queue WHERE invoice_id=? ORDER BY id DESC LIMIT 1", [$id]);
}

// Invoice list with status filter — single JOIN query (no N+1)
$status_filter = $_GET['status'] ?? 'all';
$where  = $status_filter !== 'all'
    ? "WHERE i.user_id = ? AND i.status = ?"
    : "WHERE i.user_id = ?";
$params = $status_filter !== 'all' ? [$uid, $status_filter] : [$uid];

$sort = $_GET['sort'] ?? 'id';
$orderBy = 'i.id DESC';
if ($sort === 'client') {
    $orderBy = 'c.name ASC, i.due_date ASC, i.id DESC';
} elseif ($sort === 'due') {
    $orderBy = 'i.due_date ASC, c.name ASC, i.id DESC';
}

$invoices = DB::all("
    SELECT i.id, i.invoice_number, i.status, i.currency, i.issue_date, i.due_date,
           c.name AS client_name,
           COALESCE(SUM(
               (it.unit_price_cents * it.number DIV 10)
               + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
           ), 0) AS total_cents,
           (SELECT COUNT(*) FROM payments p WHERE p.invoice_id = i.id) AS payment_n
    FROM invoices i
    LEFT JOIN clients c        ON c.id  = i.client_id
    LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
    LEFT JOIN items it         ON it.id = ii.item_id
    $where
    GROUP BY i.id, i.invoice_number, i.status, i.currency, i.issue_date, i.due_date, c.name
    ORDER BY $orderBy", $params);

$counts    = DB::all("SELECT status, COUNT(*) AS cnt FROM invoices WHERE user_id=? GROUP BY status", [$uid]);
$count_map = ['all' => DB::scalar("SELECT COUNT(*) FROM invoices WHERE user_id=?", [$uid])];
foreach ($counts as $r) $count_map[$r['status']] = $r['cnt'];

if (in_array($action, ['list', 'view'], true)) {
    require_once __DIR__ . '/../lib/SbasConfig.php';
    $bank_sync_cfg = SbasConfig::load();
    $bank_sync_ui_show = $bank_sync_cfg->enabled() || $bank_sync_cfg->mockEnabled();
    $bank_sync_conn_n = (int)DB::scalar(
        "SELECT COUNT(*) FROM bank_connections WHERE user_id=? AND status='active'",
        [$uid]
    );
}
?>

<?php
// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: HTML – zobrazenie stránky
// ═══════════════════════════════════════════════════════════════════
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-file-text text-primary me-2"></i>Faktúry</h1>
    <?php if ($action === 'list'): ?>
    <a href="<?= url('invoices', ['action'=>'create']) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nová faktúra
    </a>
    <?php endif; ?>
</div>

<?php if ($action === 'view' && isset($invoice)): ?>
<!-- ── Invoice detail ─────────────────────────────────────────────────────── -->
<?php $totals = calc_totals($inv_items); ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="mono"><?= h($invoice['invoice_number']) ?></span>
                <?= status_badge($invoice['status']) ?>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Zákazník</div>
                        <strong><?= h($invoice['client_name'] ?? '—') ?></strong><br>
                        <small><a href="mailto:<?= h($invoice['client_email']) ?>"><?= h($invoice['client_email']) ?></a></small>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small mb-1">Vystavená</div>
                        <strong><?= h($invoice['issue_date']) ?></strong>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-muted small mb-1">Splatná</div>
                        <strong class="<?= $invoice['due_date'] < today() && $invoice['status'] !== 'paid' ? 'text-danger' : '' ?>">
                            <?= h($invoice['due_date']) ?>
                        </strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr>
                            <th>#</th><th>Položka</th><th class="text-end">Množstvo</th>
                            <th class="text-end">Jedn. cena</th><th class="text-end">DPH</th>
                            <th class="text-end">Spolu</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($inv_items as $pos => $it):
                            $net = intdiv((int)$it['unit_price_cents'] * (int)$it['number'], 10);
                            $tax = intdiv($net * (int)$it['vat_bp'], 10000);
                        ?>
                            <tr>
                                <td class="text-muted"><?= $pos+1 ?></td>
                                <td>
                                    <strong><?= h($it['name']) ?></strong>
                                    <?php if ($it['description']): ?>
                                        <br><small class="text-muted"><?= h($it['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end mono"><?= qty_display((int)$it['number']) ?> <?= unit_label((int)$it['unit']) ?></td>
                                <td class="text-end mono"><?= money((int)$it['unit_price_cents'], $invoice['currency']) ?></td>
                                <td class="text-end"><?= bp_to_pct((int)$it['vat_bp']) ?> %</td>
                                <td class="text-end mono"><?= money($net + $tax, $invoice['currency']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr><td colspan="5" class="text-end">Základ DPH:</td>
                                <td class="text-end mono"><?= money($totals['subtotal'], $invoice['currency']) ?></td></tr>
                            <tr><td colspan="5" class="text-end">DPH:</td>
                                <td class="text-end mono"><?= money($totals['tax'], $invoice['currency']) ?></td></tr>
                            <tr><td colspan="5" class="text-end fw-bold">CELKOM:</td>
                                <td class="text-end mono fw-bold fs-5"><?= money($totals['total'], $invoice['currency']) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
                <?php if ($invoice['vs']): ?>
                    <small class="text-muted">VS: <span class="mono"><?= h($invoice['vs']) ?></span></small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment record (shown if paid) -->
        <?php if ($payment): ?>
        <div class="card mt-3 border-success">
            <div class="card-header bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle-fill me-2"></i>Platba zaznamenaná
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-4 text-muted">Dátum platby</dt>
                    <dd class="col-8"><?= h($payment['paid_at']) ?></dd>
                    <dt class="col-4 text-muted">Spôsob</dt>
                    <dd class="col-8"><?= h(ucfirst($payment['method'])) ?></dd>
                    <?php if ($payment['reference']): ?>
                    <dt class="col-4 text-muted">Referencia</dt>
                    <dd class="col-8 mono"><?= h($payment['reference']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-4 text-muted">Suma</dt>
                    <dd class="col-8 mono"><?= money((int)$payment['amount_cents'], $payment['currency']) ?></dd>
                    <?php if ($payment['notes']): ?>
                    <dt class="col-4 text-muted">Poznámka</dt>
                    <dd class="col-8"><?= h($payment['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <a href="<?= url('invoices', ['action'=>'download','id'=>$invoice['id']]) ?>"
               class="btn btn-sm btn-success">
               <i class="bi bi-file-earmark-pdf me-1"></i>Stiahnuť PDF
            </a>
            <a href="<?= url('templates', ['action'=>'create', 'from_invoice'=>$invoice['id']]) ?>"
               class="btn btn-sm btn-outline-primary" title="Opakovanie s rovnakými položkami ako táto faktúra">
               <i class="bi bi-files me-1"></i>Šablóna z faktúry
            </a>
            <a href="<?= url('invoices', ['action'=>'edit','id'=>$invoice['id']]) ?>"
               class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Upraviť</a>
            <?php if (!empty($can_hard_delete)): ?>
            <a href="<?= url('invoices', ['action'=>'delete','id'=>$invoice['id']]) ?>"
               class="btn btn-sm btn-outline-danger"
               data-confirm="Naozaj vymazať faktúru <?= h(addslashes($invoice['invoice_number'])) ?>?">
               <i class="bi bi-trash me-1"></i>Vymazať</a>
            <?php else: ?>
            <span class="btn btn-sm btn-outline-secondary disabled" tabindex="0"
                  title="<?= h((string)invoice_delete_blocked_reason($invoice, $payment_n_view)) ?>">
               <i class="bi bi-trash me-1"></i>Vymazať
            </span>
            <?php endif; ?>
            <?php if ($bank_sync_ui_show && $bank_sync_conn_n >= 1): ?>
            <form method="post" action="<?= url('invoices', ['action' => 'view', 'id' => (int)$invoice['id']]) ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="bank_sync_from_invoice">
                <input type="hidden" name="id" value="<?= (int)$invoice['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary" title="Rovnaké ako na zozname faktúr; cron každých 30 min">
                    <i class="bi bi-arrow-repeat me-1"></i>Synchronizovať s bankou
                </button>
            </form>
            <?php elseif ($bank_sync_ui_show): ?>
            <a href="<?= url('bank') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-link-45deg me-1"></i>Prepojiť banku</a>
            <?php endif; ?>
            <a href="<?= url('invoices') ?>" class="btn btn-sm btn-outline-secondary ms-auto">
               <i class="bi bi-arrow-left me-1"></i>Späť</a>
        </div>
    </div>

    <!-- Sidebar: status change + email + record payment -->
    <div class="col-lg-4 d-flex flex-column gap-3">

        <!-- Change status -->
        <div class="card">
            <div class="card-header">Zmeniť stav</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="set_status">
                    <input type="hidden" name="id" value="<?= h($invoice['id']) ?>">
                    <div class="mb-2">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['draft','unpaid','paid','overdue','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $invoice['status'] ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-primary w-100" type="submit">Uložiť stav</button>
                </form>
            </div>
        </div>

        <!-- Send email -->
        <div class="card">
            <div class="card-header"><i class="bi bi-envelope me-2"></i>Odoslať e-mail</div>
            <div class="card-body">
                <?php if ($last_email): ?>
                <div class="mb-2 small text-muted">
                    Naposledy: <?= h($last_email['sent_at'] ?? $last_email['created_at']) ?>
                    — <?= $last_email['status'] === 'sent'
                        ? '<span class="text-success">OK</span>'
                        : '<span class="text-danger">zlyhalo</span>' ?>
                </div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="send_email">
                    <input type="hidden" name="id" value="<?= h($invoice['id']) ?>">
                    <button class="btn btn-sm btn-outline-primary w-100" type="submit"
                            <?= empty($invoice['client_email']) ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Odoslať faktúru e-mailom
                    </button>
                    <?php if (empty($invoice['client_email'])): ?>
                        <div class="form-text text-danger">Zákazník nemá e-mail.</div>
                    <?php else: ?>
                        <div class="form-text">Na: <?= h($invoice['client_email']) ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Record payment -->
        <?php if ($invoice['status'] !== 'paid'): ?>
        <div class="card border-success">
            <div class="card-header text-success"><i class="bi bi-cash-coin me-2"></i>Zaznamenať platbu</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="record_payment">
                    <input type="hidden" name="id" value="<?= h($invoice['id']) ?>">
                    <div class="mb-2">
                        <label class="form-label small">Dátum platby</label>
                        <input type="date" name="paid_at" class="form-control form-control-sm"
                               value="<?= today() ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Spôsob platby</label>
                        <select name="method" class="form-select form-select-sm">
                            <option value="bank">Bankový prevod</option>
                            <option value="cash">Hotovosť</option>
                            <option value="card">Karta</option>
                            <option value="other">Iné</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Referencia / VS</label>
                        <input type="text" name="reference" class="form-control form-control-sm mono"
                               value="<?= h($invoice['vs'] ?? '') ?>" placeholder="Nepovinné">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Poznámka</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                               placeholder="Nepovinné">
                    </div>
                    <button class="btn btn-sm btn-success w-100" type="submit">
                        <i class="bi bi-check-circle me-1"></i>Označiť ako zaplatené
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php elseif (in_array($action, ['create','edit'])): ?>
<!-- ── Create / Edit form ─────────────────────────────────────────────────── -->
<?php
$inv = $invoice ?? [];
$isEdit = $action === 'edit';
$prefill_cid = (int)($_GET['client_id'] ?? 0);
if (!$isEdit && $prefill_cid > 0) {
    $inv['client_id'] = $prefill_cid;
}
$next_preview = '';
$next_vs_preview = '';
if ($isEdit) {
    $default_inv_num = (string)($inv['invoice_number'] ?? '');
    $default_vs      = (string)($inv['vs'] ?? '');
} else {
    $cid = (int)($inv['client_id'] ?? 0);
    $default_inv_num = $cid > 0 ? suggest_invoice_number_for_client($cid, $uid) : '';
    $default_vs      = $default_inv_num !== '' ? vs_suggest_from_invoice_number($default_inv_num) : '';
    $next_preview    = suggest_next_invoice_number($uid);
    $next_vs_preview = vs_suggest_from_invoice_number($next_preview);
}
?>
<div class="card">
    <div class="card-header">
        <i class="bi bi-file-<?= $isEdit ? 'earmark-text' : 'plus' ?> me-2"></i>
        <?= $isEdit ? 'Upraviť faktúru' : 'Nová faktúra' ?>
    </div>
    <div class="card-body">
        <form method="post" id="invoice-form">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= h($inv['id']) ?>"><?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Zákazník <span class="text-danger">*</span></label>
                    <select name="client_id" id="invoice-client-select" class="form-select" required>
                        <option value="">— Vyber zákazníka —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= h($c['id']) ?>" <?= ($inv['client_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                            <?= h($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$isEdit): ?>
                    <div class="form-text">Jednotný rad: VF–rok–poradie. Ďalšie voľné: <span class="mono"><?= h($next_preview) ?></span>.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Číslo faktúry <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" id="invoice-number-input" class="form-control mono" required
                           value="<?= h($default_inv_num) ?>"
                           placeholder="<?= $isEdit ? '' : h($next_preview) ?>"
                           <?= !$isEdit ? ' data-placeholder-inv="' . h($next_preview) . '" data-placeholder-vs="' . h($next_vs_preview) . '"' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stav</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft','unpaid','paid','overdue','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($inv['status'] ?? 'unpaid') === $s ? 'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Mena</label>
                    <select name="currency" class="form-select">
                        <?php foreach (['EUR','CZK','USD'] as $cur): ?>
                        <option value="<?= $cur ?>" <?= ($inv['currency'] ?? 'EUR') === $cur ? 'selected':'' ?>><?= $cur ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">VS</label>
                    <input type="text" name="vs" id="invoice-vs-input" class="form-control mono"
                           value="<?= h($default_vs) ?>"
                           placeholder="<?= $isEdit ? '' : h($next_vs_preview) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dátum vystavenia</label>
                    <input type="date" name="issue_date" class="form-control"
                           value="<?= h($inv['issue_date'] ?? today()) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dátum splatnosti</label>
                    <input type="date" name="due_date" class="form-control"
                           value="<?= h($inv['due_date'] ?? plus_days(14)) ?>" required>
                </div>
            </div>

            <h6 class="fw-600 mb-2">Položky faktúry</h6>
            <div class="table-responsive">
                <table class="table table-sm" id="items-table">
                    <thead><tr>
                        <th style="width:25%">Názov <span class="text-danger">*</span></th>
                        <th style="width:20%">Popis</th>
                        <th style="width:8%">Jedn.</th>
                        <th style="width:10%">Množstvo</th>
                        <th style="width:13%">Jedn. cena (€)</th>
                        <th style="width:8%">DPH (%)</th>
                        <th style="width:10%">Spolu</th>
                        <th style="width:6%"></th>
                    </tr></thead>
                    <tbody id="items-body">
                    <?php if ($isEdit && !empty($inv_items)): ?>
                        <?php foreach ($inv_items as $it): ?>
                        <tr class="item-row">
                            <td><input type="text" name="item_name[]" class="form-control" required value="<?= h($it['name']) ?>"></td>
                            <td><input type="text" name="item_desc[]" class="form-control" value="<?= h($it['description']) ?>"></td>
                            <td>
                                <select name="item_unit[]" class="form-select">
                                    <option value="0" <?= (int)$it['unit'] === 0 ? 'selected':'' ?>>ks</option>
                                    <option value="1" <?= (int)$it['unit'] === 1 ? 'selected':'' ?>>hod</option>
                                </select>
                            </td>
                            <td><input type="number" name="item_qty[]" class="form-control item-qty" step="0.1" min="0.1" value="<?= number_format((int)$it['number']/10, 1, '.', '') ?>"></td>
                            <td><input type="number" name="item_price[]" class="form-control item-price" step="0.01" min="0" value="<?= number_format((int)$it['unit_price_cents']/100, 2, '.', '') ?>"></td>
                            <td><input type="number" name="item_vat[]" class="form-control item-vat" step="1" min="0" max="100" value="<?= bp_to_pct((int)$it['vat_bp']) ?>"></td>
                            <td class="item-total text-end mono small align-middle fw-bold"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row py-0 px-2"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="item-row">
                            <td><input type="text" name="item_name[]" class="form-control" placeholder="Názov položky"></td>
                            <td><input type="text" name="item_desc[]" class="form-control" placeholder="Popis (voliteľné)"></td>
                            <td><select name="item_unit[]" class="form-select"><option value="0">ks</option><option value="1">hod</option></select></td>
                            <td><input type="number" name="item_qty[]" class="form-control item-qty" step="0.1" min="0.1" value="1.0"></td>
                            <td><input type="number" name="item_price[]" class="form-control item-price" step="0.01" min="0" value="0.00"></td>
                            <td><input type="number" name="item_vat[]" class="form-control item-vat" step="1" min="0" max="100" value="<?= (int)default_item_vat_pct() ?>"></td>
                            <td class="item-total text-end mono small align-middle fw-bold"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row py-0 px-2"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-row">
                                    <i class="bi bi-plus me-1"></i>Pridať položku
                                </button>
                            </td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="6" class="text-end text-muted small">Základ DPH:</td>
                            <td class="text-end mono small" id="foot-subtotal">—</td><td></td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="6" class="text-end text-muted small">DPH:</td>
                            <td class="text-end mono small" id="foot-tax">—</td><td></td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="6" class="text-end fw-bold">CELKOM:</td>
                            <td class="text-end mono fw-bold" id="foot-total">—</td><td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3 flex-wrap align-items-center">
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Uložiť' : 'Vytvoriť faktúru' ?>
                </button>
                <?php if ($isEdit && !empty($inv['id'])): ?>
                <a href="<?= url('templates', ['action'=>'create', 'from_invoice'=>(int)$inv['id']]) ?>"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-files me-1"></i>Šablóna z tejto faktúry
                </a>
                <?php endif; ?>
                <a href="<?= url('invoices') ?>" class="btn btn-outline-secondary btn-sm">Zrušiť</a>
            </div>
        </form>
    </div>
</div>

<script>
const DEFAULT_ITEM_VAT_PCT = <?= (int)default_item_vat_pct() ?>;
const rowTpl = `<tr class="item-row">
    <td><input type="text" name="item_name[]" class="form-control" placeholder="Názov položky"></td>
    <td><input type="text" name="item_desc[]" class="form-control" placeholder="Popis (voliteľné)"></td>
    <td><select name="item_unit[]" class="form-select"><option value="0">ks</option><option value="1">hod</option></select></td>
    <td><input type="number" name="item_qty[]" class="form-control item-qty" step="0.1" min="0.1" value="1.0"></td>
    <td><input type="number" name="item_price[]" class="form-control item-price" step="0.01" min="0" value="0.00"></td>
    <td><input type="number" name="item_vat[]" class="form-control item-vat" step="1" min="0" max="100" value="${DEFAULT_ITEM_VAT_PCT}"></td>
    <td class="item-total text-end mono small align-middle fw-bold"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row py-0 px-2"><i class="bi bi-x-lg"></i></button></td>
</tr>`;

document.getElementById('add-row').addEventListener('click', () => {
    document.getElementById('items-body').insertAdjacentHTML('beforeend', rowTpl);
    recalc();
});
document.getElementById('items-body').addEventListener('click', e => {
    if (e.target.closest('.remove-row')) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) { e.target.closest('tr').remove(); recalc(); }
    }
});
document.getElementById('items-body').addEventListener('input', recalc);

function recalc() {
    let subtotal = 0, tax = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const vat   = parseFloat(row.querySelector('.item-vat').value)   || 0;
        const net   = qty * price;
        const t     = net * vat / 100;
        row.querySelector('.item-total').textContent = (net + t).toFixed(2) + ' €';
        subtotal += net; tax += t;
    });
    document.getElementById('foot-subtotal').textContent = subtotal.toFixed(2) + ' €';
    document.getElementById('foot-tax').textContent      = tax.toFixed(2) + ' €';
    document.getElementById('foot-total').textContent    = (subtotal + tax).toFixed(2) + ' €';
}
recalc();

document.getElementById('invoice-form').addEventListener('submit', function (e) {
    const nameInputs = document.querySelectorAll('input[name="item_name[]"]');
    let hasName = false;
    let priceNoName = false;
    nameInputs.forEach((inp, idx) => {
        const row = inp.closest('.item-row');
        if (!row) return;
        const n = (inp.value || '').trim();
        const price = parseFloat(row.querySelector('.item-price')?.value || '0') || 0;
        const qty = parseFloat(row.querySelector('.item-qty')?.value || '0') || 0;
        if (n !== '') hasName = true;
        else if (price > 0) priceNoName = true;
    });
    if (!hasName) {
        e.preventDefault();
        let t = 'Vyplňte aspoň jeden názov položky. Bez názvu sa položka neuloží a suma faktúry bude 0 €.';
        if (priceNoName) t += '\n\nMáte zadanú cenu, ale chýba názov položky.';
        alert(t);
        return false;
    }
});
</script>
<?php if (!$isEdit): ?>
<script>
(function () {
    const sel = document.getElementById('invoice-client-select');
    const num = document.getElementById('invoice-number-input');
    const vs  = document.getElementById('invoice-vs-input');
    if (!sel || !num || !vs) return;
    const phInv = num.dataset.placeholderInv || '';
    const phVs = num.dataset.placeholderVs || '';
    function applyPlaceholders(invStr, vsStr) {
        num.placeholder = invStr || phInv;
        vs.placeholder = vsStr || phVs;
    }
    sel.addEventListener('change', function () {
        const id = parseInt(sel.value, 10) || 0;
        if (id < 1) {
            num.value = '';
            vs.value = '';
            applyPlaceholders(phInv, phVs);
            return;
        }
        const u = new URL(window.location.href);
        u.searchParams.set('page', 'invoices');
        u.searchParams.set('ajax', 'suggest_number');
        u.searchParams.set('client_id', String(id));
        fetch(u.toString(), { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (d.invoice_number) num.value = d.invoice_number;
                if (typeof d.vs === 'string') vs.value = d.vs;
                applyPlaceholders(d.invoice_number || '', d.vs || '');
            })
            .catch(() => {});
    });
})();
</script>
<?php endif; ?>

<?php else: ?>
<!-- ── Invoice list with status tabs ─────────────────────────────────────── -->
<?php
$tabs = ['all'=>'Všetky','unpaid'=>'Nezaplatené','overdue'=>'Po splatnosti','paid'=>'Zaplatené','draft'=>'Koncepty','cancelled'=>'Stornované'];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-3 border-bottom border-secondary-subtle">
    <ul class="nav nav-tabs flex-grow-1 mb-0 border-bottom-0" style="min-width:min(100%, 12rem);">
    <?php foreach ($tabs as $tab_key => $tab_label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === $tab_key ? 'active' : '' ?>"
               href="<?= url('invoices', ['status'=>$tab_key, 'sort'=>$sort]) ?>">
               <?= $tab_label ?>
               <?php if (isset($count_map[$tab_key])): ?>
                   <span class="badge bg-secondary ms-1"><?= $count_map[$tab_key] ?></span>
               <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
    <div class="flex-shrink-0 align-self-center">
        <?php if (!$bank_sync_ui_show): ?>
            <a href="<?= url('bank') ?>" class="btn btn-sm btn-outline-secondary" title="Nastavenie v config/sbas.json">
                <i class="bi bi-bank2 me-1"></i>Banka
            </a>
        <?php elseif ($bank_sync_conn_n < 1): ?>
            <a href="<?= url('bank') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-link-45deg me-1"></i>Prepojiť banku
            </a>
        <?php else: ?>
            <form method="post" action="<?= url('invoices', ['status' => $status_filter, 'sort' => $sort]) ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="bank_sync_from_list">
                <input type="hidden" name="ret_status" value="<?= h($status_filter) ?>">
                <input type="hidden" name="ret_sort" value="<?= h($sort) ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary" title="Rovnaké ako na stránke Banka; cron každých 30 min">
                    <i class="bi bi-arrow-repeat me-1"></i>Synchronizovať s bankou
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="page" value="invoices">
        <input type="hidden" name="status" value="<?= h($status_filter) ?>">
        <label class="small text-muted mb-0">Zoradiť:</label>
        <select name="sort" class="form-select form-select-sm" style="width:auto;min-width:11rem" onchange="this.form.submit()">
            <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>Najnovšie (ID)</option>
            <option value="client" <?= $sort === 'client' ? 'selected' : '' ?>>Zákazník (A–Z), potom splatnosť</option>
            <option value="due" <?= $sort === 'due' ? 'selected' : '' ?>>Splatnosť (najskôr)</option>
        </select>
    </form>
</div>

<form method="post" action="<?= url('invoices', ['status' => $status_filter, 'sort' => $sort]) ?>" id="bulk-form">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="bulk_set_status">
    <input type="hidden" name="ret_status" value="<?= h($status_filter) ?>">
    <input type="hidden" name="ret_sort" value="<?= h($sort) ?>">

    <div class="d-flex align-items-center gap-2 mb-2" id="bulk-bar" style="display:none !important">
        <span class="small text-muted" id="bulk-count">0 vybraných</span>
        <select name="status" class="form-select form-select-sm" style="width:auto;min-width:10rem">
            <option value="paid">Zaplatená</option>
            <option value="unpaid">Nezaplatená</option>
            <option value="overdue">Po splatnosti</option>
            <option value="draft">Koncept</option>
            <option value="cancelled">Stornovaná</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-check-all me-1"></i>Zmeniť stav
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr>
                    <th style="width:1rem"><input type="checkbox" class="form-check-input" id="bulkSelectAll"></th>
                    <th>Číslo</th><th>Zákazník</th><th>Stav</th>
                    <th>Vystavená</th><th>Splatná</th><th>Suma</th><th>Akcie</th>
                </tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv):
                    $can_del_row = invoice_delete_blocked_reason($inv, (int)($inv['payment_n'] ?? 0)) === null;
                    ?>
                    <tr>
                        <td><input type="checkbox" name="invoice_ids[]" value="<?= (int)$inv['id'] ?>" class="form-check-input bulk-inv-cb"></td>
                        <td class="inv-num"><?= h($inv['invoice_number']) ?></td>
                        <td><?= h($inv['client_name'] ?? '—') ?></td>
                        <td><?= status_badge($inv['status']) ?></td>
                        <td class="text-muted small"><?= h($inv['issue_date']) ?></td>
                        <td class="<?= ($inv['due_date'] < today() && !in_array($inv['status'],['paid','cancelled'])) ? 'text-danger fw-bold' : 'text-muted small' ?>">
                            <?= h($inv['due_date']) ?>
                        </td>
                        <td class="mono"><?= money((int)$inv['total_cents'], $inv['currency']) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('invoices', ['action'=>'view','id'=>$inv['id']]) ?>"
                                   class="btn btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="<?= url('invoices', ['action'=>'download','id'=>$inv['id']]) ?>"
                                   class="btn btn-outline-success" title="Stiahnuť PDF">
                                   <i class="bi bi-file-earmark-pdf"></i></a>
                                <a href="<?= url('invoices', ['action'=>'edit','id'=>$inv['id']]) ?>"
                                   class="btn btn-outline-secondary" title="Upraviť"><i class="bi bi-pencil"></i></a>
                                <?php if ($can_del_row): ?>
                                <a href="<?= url('invoices', ['action'=>'delete','id'=>$inv['id']]) ?>"
                                   class="btn btn-outline-danger"
                                   data-confirm="Vymazať faktúru <?= h(addslashes($inv['invoice_number'])) ?>?">
                                   <i class="bi bi-trash"></i></a>
                                <?php else: ?>
                                <span class="btn btn-outline-secondary disabled py-0 px-2" style="pointer-events:none;opacity:0.5"
                                      title="<?= h((string)invoice_delete_blocked_reason($inv, (int)($inv['payment_n'] ?? 0))) ?>"><i class="bi bi-trash"></i></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Žiadne faktúry</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
(function () {
    const bar = document.getElementById('bulk-bar');
    const countEl = document.getElementById('bulk-count');
    const allCb = document.getElementById('bulkSelectAll');
    if (!bar || !allCb) return;

    function updateBar() {
        const checked = document.querySelectorAll('.bulk-inv-cb:checked').length;
        if (checked > 0) {
            bar.style.cssText = '';
            bar.classList.add('d-flex');
        } else {
            bar.style.display = 'none';
            bar.classList.remove('d-flex');
        }
        countEl.textContent = checked + ' vybraných';
    }

    allCb.addEventListener('change', function () {
        document.querySelectorAll('.bulk-inv-cb').forEach(cb => cb.checked = this.checked);
        updateBar();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('bulk-inv-cb')) updateBar();
    });
})();
</script>
<?php endif; ?>
