<?php
/**
 * Modul: Šablóny opakovaných faktúr
 *
 * CRUD šablón, manuálne generovanie faktúry zo šablóny s odoslaním e-mailu,
 * prepínanie aktívnosti. Automatické generovanie cez cron/monthly.php.
 */

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$uid    = (int)current_user_id();
$clients = DB::all("SELECT id, name, email FROM clients WHERE user_id=? ORDER BY name", [$uid]);

/** @var ?array $tpl */
/** @var ?array $tpl_items */
$tpl = null;
$tpl_items = null;
$template_from_invoice = null;

if ($action === 'create') {
    $fid = (int)($_GET['from_invoice'] ?? 0);
    if ($fid > 0 && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $inv = DB::one("SELECT * FROM invoices WHERE id=? AND user_id=?", [$fid, $uid]);
        if ($inv) {
            $inv_items = DB::all("
                SELECT it.*, ii.position
                FROM invoice_items ii
                JOIN items it ON it.id = ii.item_id
                WHERE ii.invoice_id = ?
                ORDER BY ii.position ASC
            ", [$fid]);
            if (!empty($inv_items)) {
                $issue = new DateTimeImmutable($inv['issue_date']);
                $due   = new DateTimeImmutable($inv['due_date']);
                $due_days = max(1, $issue->diff($due)->days);
                $send_day = max(1, min(28, (int)$issue->format('j')));
                $tpl = [
                    'name'       => 'Opakovanie: ' . $inv['invoice_number'],
                    'client_id'  => (int)$inv['client_id'],
                    'currency'   => $inv['currency'],
                    'send_day'   => $send_day,
                    'due_days'   => $due_days,
                    'active'     => 1,
                ];
                $tpl_items = $inv_items;
                $template_from_invoice = $inv['invoice_number'];
            } else {
                flash_info('Faktúra ' . $inv['invoice_number'] . ' nemá položky – šablónu doplňte ručne.');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: POST handlery (CRUD, toggle, generovanie)
// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';

    if ($a === 'create' || $a === 'update') {
        $f = [
            'name'       => trim($_POST['name']       ?? ''),
            'client_id'  => (int)($_POST['client_id'] ?? 0),
            'currency'   => $_POST['currency']        ?? 'EUR',
            'send_day'   => max(1, min(28, (int)($_POST['send_day'] ?? 1))),
            'due_days'   => max(1, (int)($_POST['due_days'] ?? 14)),
            'active'     => isset($_POST['active']) ? 1 : 0,
        ];
        $line_names  = $_POST['item_name']  ?? [];
        $line_descs  = $_POST['item_desc']  ?? [];
        $line_units  = $_POST['item_unit']  ?? [];
        $line_prices = $_POST['item_price'] ?? [];
        $line_qtys   = $_POST['item_qty']   ?? [];
        $line_vats   = $_POST['item_vat']   ?? [];

        if (empty($f['name']) || !$f['client_id']) {
            flash_error('Názov a zákazník sú povinné.');
        } else {
            DB::begin();
            try {
                if ($a === 'create') {
                    DB::exec("INSERT INTO invoice_templates(user_id,name,client_id,currency,send_day,due_days,active)
                              VALUES(?,?,?,?,?,?,?)", array_merge([$uid], array_values($f)));
                    $tpl_id = (int)DB::lastId();
                } else {
                    $tpl_id = (int)$_POST['id'];
                    DB::exec("UPDATE invoice_templates SET name=?,client_id=?,currency=?,send_day=?,due_days=?,active=?
                              WHERE id=? AND user_id=?", array_merge(array_values($f), [$tpl_id, $uid]));
                    DB::exec("DELETE FROM template_items WHERE template_id=?", [$tpl_id]);
                }
                foreach ($line_names as $pos => $name) {
                    if (trim($name) === '') continue;
                    DB::exec("INSERT INTO template_items(template_id,name,description,unit,unit_price_cents,vat_bp,number,position)
                              VALUES(?,?,?,?,?,?,?,?)",
                        [$tpl_id, trim($name), trim($line_descs[$pos] ?? ''),
                         (int)($line_units[$pos] ?? 0),
                         parse_cents($line_prices[$pos] ?? '0'),
                         pct_to_bp($line_vats[$pos] ?? '0'),
                         parse_qty($line_qtys[$pos] ?? '1'),
                         $pos + 1]);
                }
                DB::commit();
                flash_success($a === 'create' ? 'Šablóna vytvorená.' : 'Šablóna aktualizovaná.');
                redirect(url('templates'));
            } catch (Throwable $e) {
                DB::rollback();
                flash_error('Chyba: ' . $e->getMessage());
            }
        }
    }

    // Toggle active/inactive
    if ($a === 'toggle') {
        $tpl_id = (int)$_POST['id'];
        DB::exec("UPDATE invoice_templates SET active = 1 - active WHERE id=? AND user_id=?", [$tpl_id, $uid]);
        redirect(url('templates'));
    }

    // Generate invoice from template NOW (manual trigger)
    if ($a === 'generate') {
        $tpl_id = (int)$_POST['id'];
        $tpl    = DB::one("SELECT * FROM invoice_templates WHERE id=? AND user_id=?", [$tpl_id, $uid]);
        $client = $tpl ? DB::one("SELECT * FROM clients WHERE id=? AND user_id=?", [$tpl['client_id'], $uid]) : null;

        if ($tpl && $client) {
            $inv_number = suggest_invoice_number_for_client((int)$tpl['client_id'], $uid);
            $issue_date = today();
            $due_date   = plus_days((int)$tpl['due_days']);
            $vs = invoice_default_vs($inv_number);

            DB::begin();
            try {
                // 1. Create invoice in DB
                DB::exec(
                    "INSERT INTO invoices(client_id,user_id,invoice_number,status,currency,issue_date,due_date,vs,template_id)
                     VALUES(?,?,?,?,?,?,?,?,?)",
                    [$tpl['client_id'], $uid, $inv_number, 'unpaid', $tpl['currency'],
                     $issue_date, $due_date, $vs, $tpl_id]
                );
                $inv_id = (int)DB::lastId();

                // 2. Create line items from template
                $tpl_items = DB::all(
                    "SELECT * FROM template_items WHERE template_id=? ORDER BY position",
                    [$tpl_id]
                );
                foreach ($tpl_items as $ti) {
                    DB::exec(
                        "INSERT INTO items(user_id,name,description,unit,unit_price_cents,vat_bp,number)
                         VALUES(?,?,?,?,?,?,?)",
                        [$uid, $ti['name'], $ti['description'], $ti['unit'],
                         $ti['unit_price_cents'], $ti['vat_bp'], $ti['number']]
                    );
                    $item_id = (int)DB::lastId();
                    DB::exec(
                        "INSERT INTO invoice_items(invoice_id,item_id,position) VALUES(?,?,?)",
                        [$inv_id, $item_id, $ti['position']]
                    );
                }
                DB::commit();
                mark_overdue_unpaid_invoices($inv_id);

                // 3. PDF len pre túto faktúru (--pdf=id), potom SMTP cez PHP (nie dávkový C++ cron,
                //    ktorý spracuje všetky čakajúce faktúry a môže spadnúť na inej položke).
                $items_for_total = DB::all(
                    "SELECT it.unit_price_cents, it.number, it.vat_bp
                     FROM invoice_items ii JOIN items it ON it.id = ii.item_id
                     WHERE ii.invoice_id = ?",
                    [$inv_id]
                );
                $totals = calc_totals($items_for_total);
                $mailer = Mailer::forUserId($uid);
                $pdf    = invoice_pdf_ensure_cached($inv_id);

                if ($pdf['ok']) {
                    $attach = 'Faktura_' . preg_replace('/[^-a-zA-Z0-9_.]/', '_', $inv_number) . '.pdf';
                    $sent   = $mailer->sendInvoiceIssuedNotice(
                        $client['email'],
                        $client['name'],
                        $inv_number,
                        $due_date,
                        $tpl['currency'],
                        $totals['total'],
                        $pdf['path'],
                        $attach
                    );
                    $err_msg = $sent ? '' : 'SMTP odoslanie zlyhalo';
                } else {
                    $sent = $mailer->sendInvoiceIssuedNotice(
                        $client['email'],
                        $client['name'],
                        $inv_number,
                        $due_date,
                        $tpl['currency'],
                        $totals['total']
                    );
                    $err_msg = $sent
                        ? ('Odoslané len textom bez PDF: ' . $pdf['message'])
                        : ($pdf['message'] . ' (SMTP tiež zlyhal)');
                }

                // 4. Log result
                DB::exec(
                    "INSERT INTO reminder_log(invoice_id,client_id,sent_at,success,error_message)
                     VALUES(?,?,NOW(),?,?)",
                    [$inv_id, $client['id'], $sent ? 1 : 0, $err_msg]
                );

                if ($sent && $pdf['ok']) {
                    flash_success("Faktúra {$inv_number} vytvorená; e-mail s PDF odoslaný na {$client['email']}.");
                } elseif ($sent) {
                    flash_info("Faktúra {$inv_number} vytvorená; e-mail odoslaný bez PDF. {$err_msg}");
                } else {
                    flash_info("Faktúra {$inv_number} vytvorená. E-mail sa nepodarilo odoslať. {$err_msg}");
                }
                redirect(url('invoices', ['action' => 'view', 'id' => $inv_id]));

            } catch (Throwable $e) {
                DB::rollback();
                flash_error('Chyba pri generovaní: ' . $e->getMessage());
            }
        } else {
            flash_error('Šablóna alebo zákazník nenájdený.');
        }
        redirect(url('templates'));
    }
}

if ($action === 'delete' && $id) {
    DB::exec("DELETE FROM invoice_templates WHERE id=? AND user_id=?", [$id, $uid]);
    flash_success('Šablóna vymazaná.');
    redirect(url('templates'));
}

if ($action === 'edit' && $id) {
    $tpl = DB::one("SELECT * FROM invoice_templates WHERE id=? AND user_id=?", [$id, $uid]);
    if (!$tpl) { flash_error('Šablóna neexistuje.'); redirect(url('templates')); }
    $tpl_items = DB::all("SELECT * FROM template_items WHERE template_id=? ORDER BY position", [$id]);
}

$templates = DB::all("
    SELECT t.*, c.name AS client_name, c.email AS client_email,
           COUNT(ti.id) AS item_count
    FROM invoice_templates t
    JOIN clients c ON c.id = t.client_id
    LEFT JOIN template_items ti ON ti.template_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.active DESC, t.send_day ASC
", [$uid]);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-files text-primary me-2"></i>Šablóny faktúr</h1>
    <a href="<?= url('templates', ['action'=>'create']) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nová šablóna
    </a>
</div>

<?php if (in_array($action, ['create','edit'])): ?>
<!-- ── Create / Edit form ─────────────────────────────────────────────────── -->
<?php
$t = $tpl ?? [];
$isEdit = $action === 'edit';
if (!empty($template_from_invoice)) {
    $tpl_rows = $tpl_items ?? [];
} elseif ($isEdit) {
    $tpl_rows = $tpl_items ?? [];
} else {
    $tpl_rows = [null];
}
?>
<div class="card">
    <div class="card-header">
        <?= $isEdit ? 'Upraviť šablónu' : 'Nová šablóna' ?>
        <?php if (!empty($template_from_invoice)): ?>
            <span class="badge bg-info text-dark ms-2">Z faktúry <?= h($template_from_invoice) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!empty($template_from_invoice)): ?>
    <div class="card-body border-bottom py-2 bg-light">
        <small class="text-muted">
            Názov, zákazník, položky, deň odoslania a splatnosť sú predvyplnené z vybranej faktúry. Upravte ich a uložte.
        </small>
    </div>
    <?php endif; ?>
    <div class="card-body">
        <form method="post" id="tpl-form">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= h($t['id']) ?>"><?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Názov šablóny <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= h($t['name'] ?? '') ?>" placeholder="Napr. Mesačný hosting">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zákazník <span class="text-danger">*</span></label>
                    <select name="client_id" class="form-select" required>
                        <option value="">— Vyber zákazníka —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= h($c['id']) ?>" <?= ($t['client_id'] ?? 0) == $c['id'] ? 'selected':'' ?>>
                            <?= h($c['name']) ?> (<?= h($c['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mena</label>
                    <select name="currency" class="form-select">
                        <?php foreach (['EUR','CZK','USD'] as $cur): ?>
                        <option value="<?= $cur ?>" <?= ($t['currency'] ?? 'EUR') === $cur ? 'selected':'' ?>><?= $cur ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Aktívna</label><br>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="active" id="active"
                               <?= ($t['active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Aktívna</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label" title="Deň v mesiaci, kedy sa faktúra automaticky vygeneruje (1–28)">
                        Deň odoslania <i class="bi bi-question-circle text-muted"></i>
                    </label>
                    <input type="number" name="send_day" class="form-control" min="1" max="28"
                           value="<?= h($t['send_day'] ?? 1) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" title="Koľko dní po vystavení je faktúra splatná">
                        Splatnosť (dni) <i class="bi bi-question-circle text-muted"></i>
                    </label>
                    <input type="number" name="due_days" class="form-control" min="1"
                           value="<?= h($t['due_days'] ?? 14) ?>">
                </div>
            </div>
            <p class="form-text small text-muted mb-0">
                Pri vygenerovaní faktúry z tejto šablóny sa variabilný symbol odvodí z čísla faktúry (napr. <span class="mono">VF20260001</span>).
            </p>

            <h6 class="fw-600 mb-2 mt-3">Položky šablóny</h6>
            <div class="table-responsive">
                <table class="table table-sm" id="tpl-table">
                    <thead><tr>
                        <th style="width:30%">Názov</th><th style="width:22%">Popis</th>
                        <th style="width:8%">Jedn.</th><th style="width:10%">Množstvo</th>
                        <th style="width:14%">Jedn. cena (€)</th><th style="width:8%">DPH (%)</th>
                        <th style="width:8%"></th>
                    </tr></thead>
                    <tbody id="tpl-body">
                    <?php foreach ($tpl_rows as $ti):
                        $tr = is_array($ti) ? $ti : [];
                        ?>
                    <tr class="tpl-row">
                        <td><input type="text" name="item_name[]" class="form-control" placeholder="Názov" value="<?= h($tr['name'] ?? '') ?>"></td>
                        <td><input type="text" name="item_desc[]" class="form-control" placeholder="Popis" value="<?= h($tr['description'] ?? '') ?>"></td>
                        <td>
                            <select name="item_unit[]" class="form-select">
                                <option value="0" <?= (($tr['unit'] ?? 0) == 0) ? 'selected':'' ?>>ks</option>
                                <option value="1" <?= (($tr['unit'] ?? 0) == 1) ? 'selected':'' ?>>hod</option>
                            </select>
                        </td>
                        <td><input type="number" name="item_qty[]" class="form-control" step="0.1" min="0.1" value="<?= number_format((int)($tr['number'] ?? 10)/10, 1, '.', '') ?>"></td>
                        <td><input type="number" name="item_price[]" class="form-control mono" step="0.01" min="0" value="<?= number_format((int)($tr['unit_price_cents'] ?? 0)/100, 2, '.', '') ?>"></td>
                        <td><input type="number" name="item_vat[]" class="form-control" step="1" min="0" max="100" value="<?= bp_to_pct((int)($tr['vat_bp'] ?? pct_to_bp((string)default_item_vat_pct()))) ?>"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-tpl py-0 px-2"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="7">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-tpl-row">
                                <i class="bi bi-plus me-1"></i>Pridať položku
                            </button>
                        </td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Uložiť' : 'Vytvoriť šablónu' ?>
                </button>
                <a href="<?= url('templates') ?>" class="btn btn-outline-secondary btn-sm">Zrušiť</a>
            </div>
        </form>
    </div>
</div>
<script>
const DEFAULT_ITEM_VAT_PCT = <?= (int)default_item_vat_pct() ?>;
const tplRowTpl = `<tr class="tpl-row">
    <td><input type="text" name="item_name[]" class="form-control" placeholder="Názov"></td>
    <td><input type="text" name="item_desc[]" class="form-control" placeholder="Popis"></td>
    <td><select name="item_unit[]" class="form-select"><option value="0">ks</option><option value="1">hod</option></select></td>
    <td><input type="number" name="item_qty[]" class="form-control" step="0.1" min="0.1" value="1.0"></td>
    <td><input type="number" name="item_price[]" class="form-control mono" step="0.01" min="0" value="0.00"></td>
    <td><input type="number" name="item_vat[]" class="form-control" step="1" min="0" max="100" value="${DEFAULT_ITEM_VAT_PCT}"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-tpl py-0 px-2"><i class="bi bi-x-lg"></i></button></td>
</tr>`;
document.getElementById('add-tpl-row').addEventListener('click', () =>
    document.getElementById('tpl-body').insertAdjacentHTML('beforeend', tplRowTpl));
document.getElementById('tpl-body').addEventListener('click', e => {
    if (e.target.closest('.remove-tpl')) {
        if (document.querySelectorAll('.tpl-row').length > 1)
            e.target.closest('tr').remove();
    }
});
</script>

<?php else: ?>
<!-- ── Template list ─────────────────────────────────────────────────────── -->
<?php if (empty($templates)): ?>
    <div class="alert alert-info">
        Žiadne šablóny. <a href="<?= url('templates', ['action'=>'create']) ?>">Vytvorte prvú!</a>
    </div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($templates as $t): ?>
    <div class="col-lg-6">
        <div class="card h-100 <?= !$t['active'] ? 'border-secondary opacity-75' : '' ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <?php if ($t['active']): ?>
                        <span class="badge bg-success me-1">Aktívna</span>
                    <?php else: ?>
                        <span class="badge bg-secondary me-1">Neaktívna</span>
                    <?php endif; ?>
                    <strong><?= h($t['name']) ?></strong>
                </span>
                <div class="btn-group btn-group-sm">
                    <a href="<?= url('templates', ['action'=>'edit','id'=>$t['id']]) ?>"
                       class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <a href="<?= url('templates', ['action'=>'delete','id'=>$t['id']]) ?>"
                       class="btn btn-outline-danger"
                       data-confirm="Vymazať šablónu <?= h(addslashes($t['name'])) ?>?">
                       <i class="bi bi-trash"></i></a>
                </div>
            </div>
            <div class="card-body">
                <dl class="row small mb-2">
                    <dt class="col-5 text-muted">Zákazník</dt>
                    <dd class="col-7"><?= h($t['client_name']) ?></dd>
                    <dt class="col-5 text-muted">E-mail</dt>
                    <dd class="col-7"><a href="mailto:<?= h($t['client_email']) ?>"><?= h($t['client_email']) ?></a></dd>
                    <dt class="col-5 text-muted">Odosiela sa</dt>
                    <dd class="col-7"><?= h($t['send_day']) ?>. deň v mesiaci</dd>
                    <dt class="col-5 text-muted">Splatnosť</dt>
                    <dd class="col-7"><?= h($t['due_days']) ?> dní po vystavení</dd>
                    <dt class="col-5 text-muted">Mena</dt>
                    <dd class="col-7"><?= h($t['currency']) ?></dd>
                    <dt class="col-5 text-muted">Položiek</dt>
                    <dd class="col-7"><?= h($t['item_count']) ?></dd>
                </dl>
            </div>
            <div class="card-footer d-flex gap-2 justify-content-between">
                <!-- Toggle active -->
                <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="toggle">
                    <input type="hidden" name="id" value="<?= h($t['id']) ?>">
                    <button class="btn btn-sm <?= $t['active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                        <?= $t['active'] ? '<i class="bi bi-pause-circle me-1"></i>Pozastaviť' : '<i class="bi bi-play-circle me-1"></i>Aktivovať' ?>
                    </button>
                </form>
                <!-- Generate now -->
                <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="generate">
                    <input type="hidden" name="id" value="<?= h($t['id']) ?>">
                    <button class="btn btn-sm btn-primary"
                            data-confirm="Vygenerovať faktúru a odoslať e-mail zákazníkovi <?= h(addslashes($t['client_name'])) ?> hneď?">
                        <i class="bi bi-send me-1"></i>Generovať &amp; odoslať teraz
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
