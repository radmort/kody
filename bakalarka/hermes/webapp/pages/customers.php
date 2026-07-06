<?php
/**
 * Modul: Správa zákazníkov
 *
 * CRUD zákazníkov (odberateľov faktúr). Detail zákazníka s prehľadom
 * jeho faktúr. Vyhľadávanie podľa mena, e-mailu alebo IČO.
 */

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$uid    = (int)current_user_id();

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: POST handlery (CRUD zákazníkov)
// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';

    $ip = trim($_POST['invoice_prefix'] ?? '');
    $ip = $ip !== '' ? mb_strtoupper(mb_substr($ip, 0, 1, 'UTF-8'), 'UTF-8') : null;

    $fields = [
        'name'            => trim($_POST['name']    ?? ''),
        'invoice_prefix'  => $ip,
        'address'         => trim($_POST['address'] ?? ''),
        'email'           => trim($_POST['email']   ?? ''),
        'phone'           => trim($_POST['phone']   ?? ''),
        'ico'             => trim($_POST['ico']     ?? '') ?: null,
        'dic'             => trim($_POST['dic']     ?? '') ?: null,
        'iban'            => trim($_POST['iban']    ?? '') ?: null,
    ];

    if (empty($fields['name']) || empty($fields['email'])) {
        flash_error('Meno a e-mail sú povinné.');
    } else {
        if ($a === 'create') {
            DB::exec("INSERT INTO clients(user_id,name,invoice_prefix,address,email,phone,ico,dic,iban)
                      VALUES(?,?,?,?,?,?,?,?,?)",
                array_merge([$uid], array_values($fields)));
            flash_success('Zákazník bol vytvorený.');
            redirect(url('customers'));
        } elseif ($a === 'update') {
            DB::exec("UPDATE clients SET name=?,invoice_prefix=?,address=?,email=?,phone=?,ico=?,dic=?,iban=?
                      WHERE id=? AND user_id=?",
                array_merge(array_values($fields), [(int)$_POST['id'], $uid]));
            flash_success('Zákazník bol aktualizovaný.');
            redirect(url('customers'));
        }
    }
}

if ($action === 'delete' && $id) {
    // Prevent delete if active invoices exist
    $inv_count = DB::scalar("SELECT COUNT(*) FROM invoices WHERE client_id=? AND user_id=? AND status IN ('unpaid','overdue')", [$id, $uid]);
    if ($inv_count > 0) {
        flash_error("Zákazník má {$inv_count} nezaplatených faktúr. Najprv ich vyriešte.");
    } else {
        DB::exec("DELETE FROM clients WHERE id=? AND user_id=?", [$id, $uid]);
        flash_success('Zákazník bol vymazaný.');
    }
    redirect(url('customers'));
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Načítanie dát pre zobrazenie
// ═══════════════════════════════════════════════════════════════════

if ($action === 'view' && $id) {
    $customer = DB::one("SELECT * FROM clients WHERE id=? AND user_id=?", [$id, $uid]);
    if (!$customer) { flash_error('Zákazník neexistuje.'); redirect(url('customers')); }
    $invoices = DB::all("SELECT i.*, c.name AS client_name FROM invoices i
                         JOIN clients c ON c.id=i.client_id
                         WHERE i.client_id=? AND i.user_id=? ORDER BY i.id DESC", [$id, $uid]);
}

if ($action === 'edit' && $id) {
    $customer = DB::one("SELECT * FROM clients WHERE id=? AND user_id=?", [$id, $uid]);
    if (!$customer) { flash_error('Zákazník neexistuje.'); redirect(url('customers')); }
}

$search    = trim($_GET['q'] ?? '');
$customers = DB::all("
    SELECT c.*,
           COUNT(i.id)                                   AS invoice_count,
           SUM(i.status IN ('unpaid','overdue'))          AS unpaid_count
    FROM clients c
    LEFT JOIN invoices i ON i.client_id = c.id
    WHERE c.user_id = ? AND (c.name LIKE ? OR c.email LIKE ? OR c.ico LIKE ?)
    GROUP BY c.id
    ORDER BY c.name ASC",
    [$uid, "%$search%", "%$search%", "%$search%"]
);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-people text-primary me-2"></i>Zákazníci</h1>
    <a href="<?= url('customers', ['action'=>'create']) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Nový zákazník
    </a>
</div>

<?php if ($action === 'view' && isset($customer)): ?>
<!-- ── Customer detail ─────────────────────────────────────────────────────── -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Detail zákazníka
                <a href="<?= url('customers', ['action'=>'edit','id'=>$customer['id']]) ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Meno</dt>
                    <dd class="col-7"><?= h($customer['name']) ?></dd>
                    <dt class="col-5 text-muted">Písm. faktúry</dt>
                    <dd class="col-7 small text-muted"><?= h($customer['invoice_prefix'] ?: '—') ?> <span class="text-muted">(nepoužíva sa; čísla VF-…)</span></dd>
                    <dt class="col-5 text-muted">E-mail</dt>
                    <dd class="col-7"><a href="mailto:<?= h($customer['email']) ?>"><?= h($customer['email']) ?></a></dd>
                    <dt class="col-5 text-muted">Telefón</dt>
                    <dd class="col-7"><?= h($customer['phone'] ?: '—') ?></dd>
                    <dt class="col-5 text-muted">Adresa</dt>
                    <dd class="col-7"><?= nl2br(h($customer['address'] ?: '—')) ?></dd>
                    <dt class="col-5 text-muted">IČO</dt>
                    <dd class="col-7 mono"><?= h($customer['ico'] ?: '—') ?></dd>
                    <dt class="col-5 text-muted">DIČ</dt>
                    <dd class="col-7 mono"><?= h($customer['dic'] ?: '—') ?></dd>
                    <dt class="col-5 text-muted">IBAN</dt>
                    <dd class="col-7 mono" style="word-break:break-all"><?= h($customer['iban'] ?: '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Faktúry zákazníka</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Číslo</th><th>Stav</th><th>Vystavená</th><th>Splatná</th><th>Suma</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($invoices as $inv):
                        $items = DB::all("SELECT it.unit_price_cents,it.number,it.vat_bp
                                          FROM invoice_items ii JOIN items it ON it.id=ii.item_id
                                          WHERE ii.invoice_id=?", [$inv['id']]);
                        $t = calc_totals($items);
                    ?>
                        <tr>
                            <td class="inv-num"><?= h($inv['invoice_number']) ?></td>
                            <td><?= status_badge($inv['status']) ?></td>
                            <td><?= h($inv['issue_date']) ?></td>
                            <td><?= h($inv['due_date']) ?></td>
                            <td class="mono"><?= money($t['total'], $inv['currency']) ?></td>
                            <td>
                                <a href="<?= url('invoices', ['action'=>'view','id'=>$inv['id']]) ?>"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2">
                                   <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="6" class="text-muted text-center py-3">Žiadne faktúry</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <a href="<?= url('customers') ?>" class="btn btn-sm btn-outline-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Späť
        </a>
    </div>
</div>

<?php elseif (in_array($action, ['create','edit'])): ?>
<!-- ── Create / Edit form ─────────────────────────────────────────────────── -->
<?php $c = $customer ?? []; $isEdit = $action === 'edit'; ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2"></i>
                <?= $isEdit ? 'Upraviť zákazníka' : 'Nový zákazník' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('customers') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= h($c['id']) ?>"><?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Meno / Názov firmy <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= h($c['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Písmeno v čísle faktúry <small>(nepoužíva sa)</small></label>
                        <input type="text" name="invoice_prefix" class="form-control mono text-uppercase"
                               value="<?= h($c['invoice_prefix'] ?? '') ?>" maxlength="1" style="max-width:4rem"
                               placeholder="" title="Zastaralé pole; čísla sú v tvare VF-rok-poradie.">
                        <div class="form-text">Čísla faktúr sú jednotné: <span class="mono">VF-2026-0001</span>, nie podľa zákazníka.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= h($c['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefón</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= h($c['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresa</label>
                        <textarea name="address" class="form-control" rows="2"><?= h($c['address'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">IČO</label>
                            <input type="text" name="ico" class="form-control mono"
                                   value="<?= h($c['ico'] ?? '') ?>" maxlength="8" placeholder="12345678">
                        </div>
                        <div class="col-6">
                            <label class="form-label">DIČ</label>
                            <input type="text" name="dic" class="form-control mono"
                                   value="<?= h($c['dic'] ?? '') ?>" maxlength="10" placeholder="1234567890">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control mono"
                               value="<?= h($c['iban'] ?? '') ?>" placeholder="SK95 1100 0000 0029 4321 7802">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Uložiť' : 'Vytvoriť' ?>
                        </button>
                        <a href="<?= url('customers') ?>" class="btn btn-outline-secondary btn-sm">Zrušiť</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Customer list ─────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex gap-2 align-items-center">
        <form method="get" class="d-flex gap-2 ms-auto">
            <input type="hidden" name="page" value="customers">
            <input type="search" name="q" class="form-control form-control-sm" style="width:220px"
                   value="<?= h($search) ?>" placeholder="Hľadať zákazníka…">
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>#</th><th>Meno</th><th>E-mail</th><th>IČO</th>
                <th>Faktúry</th><th>Nezaplatené</th><th>Akcie</th>
            </tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td class="text-muted small"><?= h($c['id']) ?></td>
                    <td><strong><?= h($c['name']) ?></strong></td>
                    <td><a href="mailto:<?= h($c['email']) ?>" class="text-decoration-none"><?= h($c['email']) ?></a></td>
                    <td class="mono"><?= h($c['ico'] ?: '—') ?></td>
                    <td><?= h($c['invoice_count']) ?></td>
                    <td>
                        <?php if ($c['unpaid_count'] > 0): ?>
                            <span class="badge bg-warning text-dark"><?= h($c['unpaid_count']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('customers', ['action'=>'view','id'=>$c['id']]) ?>"
                               class="btn btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
                            <a href="<?= url('customers', ['action'=>'edit','id'=>$c['id']]) ?>"
                               class="btn btn-outline-secondary" title="Upraviť"><i class="bi bi-pencil"></i></a>
                            <a href="<?= url('customers', ['action'=>'delete','id'=>$c['id']]) ?>"
                               class="btn btn-outline-danger"
                               data-confirm="Vymazať zákazníka <?= h(addslashes($c['name'])) ?>?"
                               title="Vymazať"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <?= $search ? 'Žiadne výsledky pre "'.h($search).'"' : 'Žiadni zákazníci. <a href="'.url('customers',['action'=>'create']).'">Pridaj prvého!</a>' ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
