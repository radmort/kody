<?php
/**
 * Modul: Hlavný prehľad (Dashboard)
 *
 * Súhrnné štatistiky, grafy tržieb a stavov faktúr, posledné faktúry
 * a aktívne šablóny. Vstupná stránka po prihlásení.
 */

$uid = (int)current_user_id();

$stats = [
    'clients'    => DB::scalar("SELECT COUNT(*) FROM clients WHERE user_id=?", [$uid]),
    'invoices'   => DB::scalar("SELECT COUNT(*) FROM invoices WHERE user_id=?", [$uid]),
    'unpaid'     => DB::scalar("SELECT COUNT(*) FROM invoices WHERE user_id=? AND status='unpaid'", [$uid]),
    'overdue'    => DB::scalar("SELECT COUNT(*) FROM invoices WHERE user_id=? AND status='overdue'", [$uid]),
    'paid_month' => DB::scalar("SELECT COUNT(*) FROM invoices
                                 WHERE user_id=? AND status='paid'
                                   AND MONTH(issue_date)=MONTH(CURDATE())
                                   AND YEAR(issue_date)=YEAR(CURDATE())", [$uid]),
    'templates'  => DB::scalar("SELECT COUNT(*) FROM invoice_templates WHERE active=1 AND user_id=?", [$uid]),
];

// Revenue this month (paid invoices, with VAT)
$revenue_month = (int)DB::scalar("
    SELECT COALESCE(SUM(
        (it.unit_price_cents * it.number DIV 10)
        + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
    ), 0)
    FROM invoices i
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it          ON it.id = ii.item_id
    WHERE i.user_id = ?
      AND i.status = 'paid'
      AND MONTH(i.issue_date) = MONTH(CURDATE())
      AND YEAR(i.issue_date)  = YEAR(CURDATE())
", [$uid]);

// Total outstanding (unpaid + overdue)
// Otvorené pohľadávky: súčet netto + DPH nezaplatených a overdue faktúr
$outstanding = (int)DB::scalar("
    SELECT COALESCE(SUM(
        (it.unit_price_cents * it.number DIV 10)
        + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
    ), 0)
    FROM invoices i
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it          ON it.id = ii.item_id
    WHERE i.user_id = ?
      AND i.status IN ('unpaid','overdue')
", [$uid]);

$reminders_today = (int)DB::scalar("
    SELECT COUNT(*) FROM reminder_log rl
    JOIN invoices i ON i.id = rl.invoice_id
    WHERE i.user_id = ? AND DATE(rl.sent_at) = CURDATE() AND rl.success = 1
", [$uid]);

// Recent invoices – single JOIN query, no N+1
$recent_invoices = DB::all("
    SELECT i.id, i.invoice_number, i.status, i.currency, i.due_date,
           c.name AS client_name,
           COALESCE(SUM(
               (it.unit_price_cents * it.number DIV 10)
               + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
           ), 0) AS total_cents
    FROM invoices i
    LEFT JOIN clients c        ON c.id  = i.client_id
    LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
    LEFT JOIN items it         ON it.id = ii.item_id
    WHERE i.user_id = ?
    GROUP BY i.id, i.invoice_number, i.status, i.currency, i.due_date, c.name
    ORDER BY i.id DESC
    LIMIT 8
", [$uid]);

$upcoming = DB::all("
    SELECT t.*, c.name AS client_name
    FROM invoice_templates t
    JOIN clients c ON c.id = t.client_id
    WHERE t.user_id = ? AND t.active = 1
    ORDER BY t.send_day ASC
    LIMIT 5
", [$uid]);

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Dáta pre grafy (tržby 6M, stavy faktúr)
// ═══════════════════════════════════════════════════════════════════

// Monthly revenue last 6 months (zero-fill missing months)
$monthly_raw = DB::all("
    SELECT DATE_FORMAT(i.issue_date, '%Y-%m') AS ym,
           COALESCE(SUM(
               (it.unit_price_cents * it.number DIV 10)
               + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
           ), 0) AS cents
    FROM invoices i
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it          ON it.id = ii.item_id
    WHERE i.user_id = ?
      AND i.status = 'paid'
      AND i.issue_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
", [$uid]);
$rev_by_month = [];
for ($i = 5; $i >= 0; $i--) {
    $rev_by_month[date('Y-m', strtotime("-{$i} months"))] = 0;
}
foreach ($monthly_raw as $r) {
    if (isset($rev_by_month[$r['ym']])) {
        $rev_by_month[$r['ym']] = (int)$r['cents'];
    }
}
$chart_rev_labels = [];
$chart_rev_data   = [];
foreach ($rev_by_month as $ym => $cents) {
    [$y, $m] = explode('-', $ym);
    $chart_rev_labels[] = date('M y', mktime(0,0,0,(int)$m,1,(int)$y));
    $chart_rev_data[]   = round($cents / 100, 2);
}

// Invoice status breakdown for doughnut
$status_raw = DB::all("SELECT status, COUNT(*) AS cnt FROM invoices WHERE user_id=? GROUP BY status", [$uid]);
$status_map = ['draft'=>'Koncepty','unpaid'=>'Nezaplatené','paid'=>'Zaplatené','overdue'=>'Po splatnosti','cancelled'=>'Stornované'];
$status_clr = ['draft'=>'#94a3b8','unpaid'=>'#f59e0b','paid'=>'#22c55e','overdue'=>'#ef4444','cancelled'=>'#374151'];
$chart_st_labels = $chart_st_data = $chart_st_colors = [];
foreach ($status_raw as $r) {
    $chart_st_labels[] = $status_map[$r['status']] ?? $r['status'];
    $chart_st_data[]   = (int)$r['cnt'];
    $chart_st_colors[] = $status_clr[$r['status']] ?? '#94a3b8';
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-speedometer2 text-primary me-2"></i>Prehľad</h1>
    <small class="text-muted">Dnes: <strong><?= date('d.m.Y') ?></strong></small>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Zákazníci',             $stats['clients'],    'bi-people-fill',             'primary'],
        ['Faktúry celkom',        $stats['invoices'],   'bi-file-text-fill',          'secondary'],
        ['Nezaplatené',           $stats['unpaid'],     'bi-clock-fill',              'warning'],
        ['Po splatnosti',         $stats['overdue'],    'bi-exclamation-triangle-fill','danger'],
        ['Zaplatené tento mes.',  $stats['paid_month'], 'bi-check-circle-fill',       'success'],
        ['Aktívne šablóny',       $stats['templates'],  'bi-files-alt',               'info'],
    ];
    foreach ($cards as [$label, $value, $icon, $color]): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100" style="border-left-color:var(--bs-<?= $color ?>)">
            <div class="card-body d-flex flex-column gap-1">
                <i class="bi <?= $icon ?> text-<?= $color ?>" style="font-size:1.4rem"></i>
                <div class="stat-value"><?= h($value) ?></div>
                <div class="stat-label"><?= h($label) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Revenue + outstanding + reminders -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10">
                    <i class="bi bi-currency-euro text-success" style="font-size:2rem"></i>
                </div>
                <div>
                    <div class="text-muted small">Tržby tento mesiac</div>
                    <div class="fw-bold fs-5 mono"><?= money($revenue_month) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                    <i class="bi bi-hourglass-split text-warning" style="font-size:2rem"></i>
                </div>
                <div>
                    <div class="text-muted small">Pohľadávky (nezaplatené)</div>
                    <div class="fw-bold fs-5 mono <?= $outstanding > 0 ? 'text-warning' : '' ?>">
                        <?= money($outstanding) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10">
                    <i class="bi bi-send-check text-primary" style="font-size:2rem"></i>
                </div>
                <div>
                    <div class="text-muted small">Upomienky odoslané dnes</div>
                    <div class="fw-bold fs-5"><?= h($reminders_today) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2"></i>Tržby – posledných 6 mesiacov</span>
                <a href="<?= url('analytics') ?>" class="btn btn-sm btn-outline-primary">Celá analytika</a>
            </div>
            <div class="card-body">
                <canvas id="chartRevenue" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Stav faktúr</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartStatus" style="max-height:200px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent invoices + sidebar -->
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-text me-2"></i>Posledné faktúry</span>
                <a href="<?= url('invoices') ?>" class="btn btn-sm btn-outline-primary">Všetky</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Číslo</th><th>Zákazník</th><th>Suma</th>
                        <th>Splatnosť</th><th>Stav</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recent_invoices as $inv): ?>
                        <tr>
                            <td><span class="inv-num"><?= h($inv['invoice_number']) ?></span></td>
                            <td><?= h($inv['client_name'] ?? '—') ?></td>
                            <td class="mono"><?= money((int)$inv['total_cents'], $inv['currency']) ?></td>
                            <td class="<?= ($inv['due_date'] < today() && !in_array($inv['status'],['paid','cancelled'])) ? 'text-danger fw-bold' : 'text-muted small' ?>">
                                <?= h($inv['due_date'] ?? '—') ?>
                            </td>
                            <td><?= status_badge($inv['status']) ?></td>
                            <td>
                                <a href="<?= url('invoices', ['action'=>'view','id'=>$inv['id']]) ?>"
                                   class="btn btn-xs btn-sm btn-outline-secondary py-0 px-2">
                                   <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_invoices)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Žiadne faktúry</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-2"></i>Aktívne šablóny</span>
                <a href="<?= url('templates') ?>" class="btn btn-sm btn-outline-primary">Všetky</a>
            </div>
            <ul class="list-group list-group-flush">
            <?php foreach ($upcoming as $tpl): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                    <div>
                        <div class="fw-500"><?= h($tpl['name']) ?></div>
                        <small class="text-muted"><?= h($tpl['client_name']) ?></small>
                    </div>
                    <span class="badge bg-primary rounded-pill">
                        <?= h($tpl['send_day']) ?>. v mes.
                    </span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($upcoming)): ?>
                <li class="list-group-item text-muted text-center py-4">Žiadne šablóny</li>
            <?php endif; ?>
            </ul>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-lightning me-2"></i>Rýchle akcie</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= url('invoices', ['action'=>'create']) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Nová faktúra
                </a>
                <a href="<?= url('customers', ['action'=>'create']) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Nový zákazník
                </a>
                <a href="<?= url('templates', ['action'=>'create']) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-plus me-1"></i>Nová šablóna
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue bar chart
new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_rev_labels) ?>,
        datasets: [{
            label: 'Tržby (€)',
            data: <?= json_encode($chart_rev_data) ?>,
            backgroundColor: 'rgba(79,70,229,0.75)',
            borderColor: '#4f46e5',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => v.toLocaleString('sk-SK', {style:'currency', currency:'EUR', minimumFractionDigits:0})
                }
            }
        }
    }
});

// Status doughnut chart
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_st_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_st_data) ?>,
            backgroundColor: <?= json_encode($chart_st_colors) ?>,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 8 } }
        }
    }
});
</script>
