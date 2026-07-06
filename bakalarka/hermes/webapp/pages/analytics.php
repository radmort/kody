<?php
/**
 * Modul: Analytika a grafy
 *
 * Podrobné štatistiky: celkové tržby, pohľadávky, mesačný trend (12 mes.),
 * rozloženie stavov faktúr, top 5 zákazníkov, projekcia z aktívnych šablón.
 */

$uid = (int)current_user_id();

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Dátové dotazy pre grafy a štatistiky
// ═══════════════════════════════════════════════════════════════════

// Summary stats
// Celkové tržby: suma netto + DPH len za zaplatené faktúry
$total_revenue = (int)DB::scalar("
    SELECT COALESCE(SUM(
        (it.unit_price_cents * it.number DIV 10)
        + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
    ), 0)
    FROM invoices i
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it          ON it.id = ii.item_id
    WHERE i.user_id = ? AND i.status = 'paid'
", [$uid]);

$total_outstanding = (int)DB::scalar("
    SELECT COALESCE(SUM(
        (it.unit_price_cents * it.number DIV 10)
        + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
    ), 0)
    FROM invoices i
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it          ON it.id = ii.item_id
    WHERE i.user_id = ? AND i.status IN ('unpaid','overdue')
", [$uid]);

// Monthly revenue & invoice counts – last 12 months (zero-fill)
$monthly_raw = DB::all("
    SELECT DATE_FORMAT(i.issue_date, '%Y-%m') AS ym,
           COUNT(DISTINCT i.id) AS invoice_count,
           COALESCE(SUM(
               (it.unit_price_cents * it.number DIV 10)
               + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
           ), 0) AS paid_cents,
           SUM(i.status IN ('unpaid','overdue')) AS pending_count,
           SUM(i.status = 'paid') AS paid_count
    FROM invoices i
    LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
    LEFT JOIN items it         ON it.id = ii.item_id
    WHERE i.user_id = ?
      AND i.issue_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
", [$uid]);

$monthly = [];
for ($i = 11; $i >= 0; $i--) {
    $monthly[date('Y-m', strtotime("-{$i} months"))] = [
        'revenue'       => 0,
        'invoice_count' => 0,
        'paid_count'    => 0,
        'pending_count' => 0,
    ];
}
foreach ($monthly_raw as $r) {
    if (isset($monthly[$r['ym']])) {
        $monthly[$r['ym']] = [
            'revenue'       => round((int)$r['paid_cents'] / 100, 2),
            'invoice_count' => (int)$r['invoice_count'],
            'paid_count'    => (int)$r['paid_count'],
            'pending_count' => (int)$r['pending_count'],
        ];
    }
}

$chart_labels    = [];
$chart_revenue   = [];
$chart_inv_total = [];
$chart_inv_paid  = [];
$chart_inv_pend  = [];

foreach ($monthly as $ym => $d) {
    [$y, $m] = explode('-', $ym);
    $chart_labels[]    = date('M y', mktime(0,0,0,(int)$m,1,(int)$y));
    $chart_revenue[]   = $d['revenue'];
    $chart_inv_total[] = $d['invoice_count'];
    $chart_inv_paid[]  = $d['paid_count'];
    $chart_inv_pend[]  = $d['pending_count'];
}

// Invoice status breakdown
$status_raw = DB::all("SELECT status, COUNT(*) AS cnt FROM invoices WHERE user_id=? GROUP BY status", [$uid]);
$status_map = ['draft'=>'Koncepty','unpaid'=>'Nezaplatené','paid'=>'Zaplatené','overdue'=>'Po splatnosti','cancelled'=>'Stornované'];
$status_clr = ['draft'=>'#94a3b8','unpaid'=>'#f59e0b','paid'=>'#22c55e','overdue'=>'#ef4444','cancelled'=>'#374151'];
$st_labels = $st_data = $st_colors = [];
foreach ($status_raw as $r) {
    $st_labels[] = $status_map[$r['status']] ?? $r['status'];
    $st_data[]   = (int)$r['cnt'];
    $st_colors[] = $status_clr[$r['status']] ?? '#94a3b8';
}

// Top 5 clients by total revenue (all time)
$top_clients = DB::all("
    SELECT c.name,
           COALESCE(SUM(
               (it.unit_price_cents * it.number DIV 10)
               + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
           ), 0) AS total_cents,
           COUNT(DISTINCT i.id) AS invoice_count
    FROM clients c
    JOIN invoices i ON i.client_id = c.id AND i.status = 'paid' AND i.user_id = ?
    JOIN invoice_items ii ON ii.invoice_id = i.id
    JOIN items it ON it.id = ii.item_id
    WHERE c.user_id = ?
    GROUP BY c.id, c.name
    ORDER BY total_cents DESC
    LIMIT 5
", [$uid, $uid]);

$top_client_labels  = array_column($top_clients, 'name');
$top_client_revenue = array_map(fn($r) => round((int)$r['total_cents'] / 100, 2), $top_clients);

// Monthly revenue projection from active templates
// Projekcia mesačného príjmu zo súčtu aktívnych šablón (netto + DPH)
$projected_monthly = (int)DB::scalar("
    SELECT COALESCE(SUM(
        (ti.unit_price_cents * ti.number DIV 10)
        + (ti.unit_price_cents * ti.number DIV 10) * ti.vat_bp DIV 10000
    ), 0)
    FROM invoice_templates t
    JOIN template_items ti ON ti.template_id = t.id
    WHERE t.active = 1 AND t.user_id = ?
", [$uid]);

// Average monthly revenue (last 3 months, paid only)
$avg_3m = (float)DB::scalar("
    SELECT COALESCE(AVG(monthly_total), 0) FROM (
        SELECT DATE_FORMAT(i.issue_date, '%Y-%m') AS ym,
               SUM(
                   (it.unit_price_cents * it.number DIV 10)
                   + (it.unit_price_cents * it.number DIV 10) * it.vat_bp DIV 10000
               ) AS monthly_total
        FROM invoices i
        JOIN invoice_items ii ON ii.invoice_id = i.id
        JOIN items it ON it.id = ii.item_id
        WHERE i.user_id = ?
          AND i.status = 'paid'
          AND i.issue_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY ym
    ) AS sub
", [$uid]);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-bar-chart-line text-primary me-2"></i>Analytika</h1>
    <small class="text-muted">Dnes: <strong><?= date('d.m.Y') ?></strong></small>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card h-100" style="border-left-color:#22c55e">
            <div class="card-body">
                <div class="text-muted small mb-1">Celkové tržby (zaplatené)</div>
                <div class="stat-value text-success fs-4"><?= money($total_revenue) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100" style="border-left-color:#f59e0b">
            <div class="card-body">
                <div class="text-muted small mb-1">Otvorené pohľadávky</div>
                <div class="stat-value text-warning fs-4"><?= money($total_outstanding) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100" style="border-left-color:#4f46e5">
            <div class="card-body">
                <div class="text-muted small mb-1">Priemerné mesačné tržby (3M)</div>
                <div class="stat-value text-primary fs-4"><?= money((int)round($avg_3m)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100" style="border-left-color:#0ea5e9">
            <div class="card-body">
                <div class="text-muted small mb-1">Projekcia (aktívne šablóny/mes.)</div>
                <div class="stat-value text-info fs-4"><?= money($projected_monthly) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue trend (12 months) -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up me-2"></i>Mesačné tržby – posledných 12 mesiacov</div>
            <div class="card-body">
                <canvas id="chartRevenue12" height="70"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Invoice count trend + status doughnut -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart-steps me-2"></i>Vytvorené faktúry – posledných 12 mesiacov</div>
            <div class="card-body">
                <canvas id="chartInvoices" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Stav faktúr (celkovo)</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartStatusFull" style="max-height:240px"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top clients -->
<?php if (!empty($top_clients)): ?>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-trophy me-2"></i>Top 5 zákazníkov podľa tržieb</div>
            <div class="card-body">
                <canvas id="chartTopClients" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-table me-2"></i>Detailný prehľad</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th>#</th><th>Zákazník</th><th>Faktúry</th><th>Tržby</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($top_clients as $i => $tc): ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td><?= h($tc['name']) ?></td>
                            <td><?= h($tc['invoice_count']) ?></td>
                            <td class="mono fw-bold"><?= money((int)$tc['total_cents']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Projection note -->
<div class="card bg-light border-0 mb-4">
    <div class="card-body small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Projekcia</strong> je vypočítaná zo súčtu aktívnych šablón faktúr.
        <strong>Priemerné tržby (3M)</strong> zahŕňajú iba zaplatené faktúry za posledné 3 mesiace.
        Všetky sumy sú vrátane DPH.
    </div>
</div>

<script>
// 12-month revenue bar chart
new Chart(document.getElementById('chartRevenue12'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Tržby (€)',
            data: <?= json_encode($chart_revenue) ?>,
            backgroundColor: 'rgba(79,70,229,0.7)',
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
                    callback: v => v.toLocaleString('sk-SK', {
                        style: 'currency', currency: 'EUR', minimumFractionDigits: 0
                    })
                }
            }
        }
    }
});

// Invoice count stacked bar chart
new Chart(document.getElementById('chartInvoices'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            {
                label: 'Zaplatené',
                data: <?= json_encode($chart_inv_paid) ?>,
                backgroundColor: 'rgba(34,197,94,0.75)',
                borderRadius: 3,
            },
            {
                label: 'Nezaplatené / Overdue',
                data: <?= json_encode($chart_inv_pend) ?>,
                backgroundColor: 'rgba(245,158,11,0.75)',
                borderRadius: 3,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
        },
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        }
    }
});

// Status doughnut
new Chart(document.getElementById('chartStatusFull'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($st_labels) ?>,
        datasets: [{
            data: <?= json_encode($st_data) ?>,
            backgroundColor: <?= json_encode($st_colors) ?>,
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

<?php if (!empty($top_clients)): ?>
// Top clients horizontal bar
new Chart(document.getElementById('chartTopClients'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($top_client_labels) ?>,
        datasets: [{
            label: 'Tržby (€)',
            data: <?= json_encode($top_client_revenue) ?>,
            backgroundColor: [
                'rgba(79,70,229,0.75)',
                'rgba(14,165,233,0.75)',
                'rgba(34,197,94,0.75)',
                'rgba(245,158,11,0.75)',
                'rgba(239,68,68,0.75)',
            ],
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    callback: v => v.toLocaleString('sk-SK', {
                        style: 'currency', currency: 'EUR', minimumFractionDigits: 0
                    })
                }
            }
        }
    }
});
<?php endif; ?>
</script>
