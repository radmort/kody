<?php
/**
 * Modul: Katalóg položiek
 *
 * CRUD pre globálne položky (služby/produkty). Položky sa používajú
 * pri tvorbe faktúr a šablón. Mazanie len ak nie sú naviazané na faktúru.
 */

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$uid    = (int)current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';
    $f = [
        'name'             => trim($_POST['name'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'unit'             => (int)($_POST['unit'] ?? 0),
        'unit_price_cents' => parse_cents($_POST['unit_price'] ?? '0'),
        'vat_bp'           => pct_to_bp($_POST['vat_pct'] ?? '0'),
        'number'           => parse_qty($_POST['number'] ?? '1'),
    ];
    if (empty($f['name'])) {
        flash_error('Názov položky je povinný.');
    } else {
        if ($a === 'create') {
            DB::exec("INSERT INTO items(user_id,name,description,unit,unit_price_cents,vat_bp,number)
                      VALUES(?,?,?,?,?,?,?)", array_merge([$uid], array_values($f)));
            flash_success('Položka vytvorená.');
        } elseif ($a === 'update') {
            DB::exec("UPDATE items SET name=?,description=?,unit=?,unit_price_cents=?,vat_bp=?,number=?
                      WHERE id=? AND user_id=?", array_merge(array_values($f), [(int)$_POST['id'], $uid]));
            flash_success('Položka aktualizovaná.');
        }
        redirect(url('items'));
    }
}

if ($action === 'delete' && $id) {
    // Only delete standalone items (not linked to invoices)
    $linked = DB::scalar("SELECT COUNT(*) FROM invoice_items WHERE item_id=?", [$id]);
    if ($linked > 0) {
        flash_error("Položka je používaná v {$linked} faktúrach a nemôže byť vymazaná.");
    } else {
        DB::exec("DELETE FROM items WHERE id=? AND user_id=?", [$id, $uid]);
        flash_success('Položka vymazaná.');
    }
    redirect(url('items'));
}

if ($action === 'edit' && $id) {
    $item = DB::one("SELECT * FROM items WHERE id=? AND user_id=?", [$id, $uid]);
    if (!$item) { flash_error('Položka neexistuje.'); redirect(url('items')); }
}

$items = DB::all("SELECT i.*, (SELECT COUNT(*) FROM invoice_items ii WHERE ii.item_id=i.id) AS uses
                  FROM items i WHERE i.user_id=? ORDER BY i.id DESC", [$uid]);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-box-seam text-primary me-2"></i>Katalóg položiek</h1>
    <a href="<?= url('items', ['action'=>'create']) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Nová položka
    </a>
</div>

<?php if (in_array($action, ['create','edit'])): ?>
<?php $it = $item ?? []; $isEdit = $action === 'edit'; ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><?= $isEdit ? 'Upraviť položku' : 'Nová katalogová položka' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= h($it['id']) ?>"><?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Názov <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?= h($it['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <input type="text" name="description" class="form-control" value="<?= h($it['description'] ?? '') ?>">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label">Jednotka</label>
                            <select name="unit" class="form-select">
                                <option value="0" <?= (($it['unit'] ?? 0) == 0) ? 'selected':'' ?>>ks</option>
                                <option value="1" <?= (($it['unit'] ?? 0) == 1) ? 'selected':'' ?>>hod</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Jedn. cena (€)</label>
                            <input type="number" name="unit_price" class="form-control mono" step="0.01" min="0"
                                   value="<?= number_format((int)($it['unit_price_cents'] ?? 0)/100, 2, '.', '') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">DPH (%)</label>
                            <input type="number" name="vat_pct" class="form-control" step="1" min="0" max="100"
                                   value="<?= bp_to_pct((int)($it['vat_bp'] ?? pct_to_bp((string)default_item_vat_pct()))) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Predvolené množstvo</label>
                        <input type="number" name="number" class="form-control" step="0.1" min="0.1"
                               value="<?= number_format((int)($it['number'] ?? 10)/10, 1, '.', '') ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Uložiť' : 'Vytvoriť' ?>
                        </button>
                        <a href="<?= url('items') ?>" class="btn btn-outline-secondary btn-sm">Zrušiť</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Názov</th><th>Jedn.</th><th>Jedn. cena</th><th>DPH</th>
                <th>Pred. množ.</th><th>Použitý vo fakt.</th><th>Akcie</th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <strong><?= h($it['name']) ?></strong>
                        <?php if ($it['description']): ?><br><small class="text-muted"><?= h($it['description']) ?></small><?php endif; ?>
                    </td>
                    <td><?= unit_label((int)$it['unit']) ?></td>
                    <td class="mono"><?= money((int)$it['unit_price_cents']) ?></td>
                    <td><?= bp_to_pct((int)$it['vat_bp']) ?> %</td>
                    <td class="mono"><?= qty_display((int)$it['number']) ?></td>
                    <td><?= $it['uses'] > 0 ? "<span class=\"badge bg-secondary\">{$it['uses']}</span>" : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('items', ['action'=>'edit','id'=>$it['id']]) ?>"
                               class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <?php if ($it['uses'] == 0): ?>
                            <a href="<?= url('items', ['action'=>'delete','id'=>$it['id']]) ?>"
                               class="btn btn-outline-danger"
                               data-confirm="Vymazať položku <?= h(addslashes($it['name'])) ?>?">
                               <i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Žiadne položky v katalógu</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
