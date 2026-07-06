<?php
/**
 * Modul: Prehľad upomienok
 *
 * Zobrazenie logu odoslaných upomienok (úspešné / zlyhané) s filtrom
 * a štatistikami. Dáta z tabulky reminder_log.
 */

$uid = (int)current_user_id();

$filter = $_GET['filter'] ?? 'all'; // all | ok | fail
$limit  = 50;

$where = match($filter) {
    'ok'   => 'AND rl.success = 1',
    'fail' => 'AND rl.success = 0',
    default => '',
};

$logs = DB::all("
    SELECT rl.*,
           i.invoice_number,
           c.name  AS client_name,
           c.email AS client_email
    FROM reminder_log rl
    INNER JOIN invoices i ON i.id = rl.invoice_id AND i.user_id = ?
    LEFT JOIN clients  c ON c.id = rl.client_id
    WHERE 1=1 $where
    ORDER BY rl.sent_at DESC
    LIMIT $limit
", [$uid]);

$stats = [
    'total'   => DB::scalar("SELECT COUNT(*) FROM reminder_log rl JOIN invoices i ON i.id = rl.invoice_id AND i.user_id = ?", [$uid]),
    'success' => DB::scalar("SELECT COUNT(*) FROM reminder_log rl JOIN invoices i ON i.id = rl.invoice_id AND i.user_id = ? WHERE rl.success=1", [$uid]),
    'failed'  => DB::scalar("SELECT COUNT(*) FROM reminder_log rl JOIN invoices i ON i.id = rl.invoice_id AND i.user_id = ? WHERE rl.success=0", [$uid]),
    'today'   => DB::scalar("SELECT COUNT(*) FROM reminder_log rl JOIN invoices i ON i.id = rl.invoice_id AND i.user_id = ? WHERE DATE(rl.sent_at)=CURDATE()", [$uid]),
];
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-send-check text-primary me-2"></i>Upomienky</h1>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    $sc = [
        ['Celkom odoslané',  $stats['total'],   'secondary', 'bi-envelope'],
        ['Úspešné',          $stats['success'], 'success',   'bi-check-circle'],
        ['Zlyhané',          $stats['failed'],  'danger',    'bi-x-circle'],
        ['Dnes odoslané',    $stats['today'],   'primary',   'bi-calendar-check'],
    ];
    foreach ($sc as [$label, $value, $color, $icon]): ?>
    <div class="col-6 col-md-3">
        <div class="card stat-card" style="border-left-color:var(--bs-<?= $color ?>)">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:1.6rem"></i>
                <div>
                    <div class="stat-value"><?= h($value) ?></div>
                    <div class="stat-label"><?= h($label) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<ul class="nav nav-tabs mb-3">
    <?php foreach (['all'=>'Všetky','ok'=>'Úspešné','fail'=>'Zlyhané'] as $k => $l): ?>
    <li class="nav-item">
        <a class="nav-link <?= $filter === $k ? 'active':'' ?>" href="<?= url('reminders', ['filter'=>$k]) ?>">
            <?= $l ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr>
                <th>Čas odoslania</th><th>Faktúra</th><th>Zákazník</th>
                <th>E-mail</th><th>Výsledok</th><th>Chyba</th>
            </tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="mono text-muted small"><?= h($log['sent_at']) ?></td>
                    <td>
                        <?php if ($log['invoice_number']): ?>
                        <a href="<?= url('invoices', ['action'=>'view','id'=>$log['invoice_id']]) ?>"
                           class="inv-num text-decoration-none"><?= h($log['invoice_number']) ?></a>
                        <?php else: ?>
                            <span class="text-muted">#<?= h($log['invoice_id']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($log['client_name'] ?? '—') ?></td>
                    <td><a href="mailto:<?= h($log['client_email']) ?>"><?= h($log['client_email'] ?? '—') ?></a></td>
                    <td>
                        <?php if ($log['success']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="bi bi-x-lg"></i> FAIL</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= h($log['error_message'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Žiadne záznamy</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($logs) === $limit): ?>
    <div class="card-footer text-muted small">Zobrazených posledných <?= $limit ?> záznamov.</div>
    <?php endif; ?>
</div>
