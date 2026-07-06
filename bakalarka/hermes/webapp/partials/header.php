<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?> · Fakturačný systém</title>

    <!--
      UI stack: primárne Bootstrap 5 (+ Bootstrap Icons) z CDN; rozloženie a komponenty cez triedy BS.
      Vlastný <style> nižšie len dopĺňa tému (prepísanie BS CSS premenných, navbar, drobné úpravy) — nie celostránkový „ručný“ CSS layout.
    -->
    <!-- Google Fonts: Outfit (UI) + IBM Plex Mono (data) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js 4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bs-body-font-family: 'Outfit', sans-serif;
            --bs-primary:          #4f46e5;
            --bs-primary-rgb:      79, 70, 229;
            --hermes-navy:         #0f172a;
            --hermes-slate:        #1e293b;
            --hermes-border:       #e2e8f0;
            --hermes-muted:        #64748b;
        }
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #1e293b; }
        code, .mono { font-family: 'IBM Plex Mono', monospace; font-size: .875em; }

        /* ── Navbar ── */
        .navbar-hermes {
            background: var(--hermes-navy);
            padding: .75rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.4);
        }
        .navbar-hermes .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -.02em;
            color: #fff;
        }
        .navbar-hermes .navbar-brand span { color: #818cf8; }
        .navbar-hermes .nav-link {
            color: #94a3b8;
            font-weight: 500;
            font-size: .9rem;
            padding: .4rem .75rem;
            border-radius: 6px;
            transition: color .15s, background .15s;
        }
        .navbar-hermes .nav-link:hover,
        .navbar-hermes .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }
        .navbar-hermes .nav-link .bi { margin-right: .35rem; }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--hermes-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--hermes-border);
            font-weight: 600;
            font-size: .9rem;
            letter-spacing: -.01em;
            padding: .9rem 1.25rem;
            border-radius: 12px 12px 0 0 !important;
        }

        /* ── Stat cards ── */
        .stat-card { border-left: 4px solid var(--bs-primary); }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .8rem; color: var(--hermes-muted); text-transform: uppercase; letter-spacing: .05em; }

        /* ── Tables ── */
        .table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--hermes-muted);
            font-weight: 600;
            border-top: none;
        }
        .table td { vertical-align: middle; font-size: .875rem; }
        .inv-num { font-family: 'IBM Plex Mono', monospace; font-size: .82rem; }

        /* ── Buttons ── */
        .btn-primary { background: var(--bs-primary); border-color: var(--bs-primary); }
        .btn-primary:hover { background: #4338ca; border-color: #4338ca; }

        /* ── Breadcrumb strip ── */
        .page-header {
            padding: 1.25rem 0 .75rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--hermes-border);
        }
        .page-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }

        /* ── Status select styling ── */
        .form-select, .form-control {
            font-size: .875rem;
            border-radius: 8px;
        }

        /* ── Line-item table in invoice form ── */
        #items-table td { padding: .35rem .5rem; }
        #items-table .form-control, #items-table .form-select { padding: .3rem .5rem; }

        /* ── Template toggle ── */
        .toggle-active { cursor: pointer; }

        /* ── Sidebar nav (mobile collapse) ── */
        @media (max-width: 767px) {
            .stat-card .stat-value { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<!-- ── Top Navbar ──────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-hermes navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= url('dashboard') ?>">
            <i class="bi bi-receipt-cutoff"></i> Hermes<span>.sk</span>
        </a>
        <button class="navbar-toggler border-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <?php if (function_exists('current_user_id') && current_user_id()): ?>
            <ul class="navbar-nav ms-3 gap-1">
                <?php
                $nav_items = [
                    'dashboard'  => ['bi-speedometer2',    'Prehľad'],
                    'invoices'   => ['bi-file-text',       'Faktúry'],
                    'customers'  => ['bi-people',          'Zákazníci'],
                    'templates'  => ['bi-files',           'Šablóny'],
                    'reminders'  => ['bi-send-check',      'Upomienky'],
                    'analytics'  => ['bi-bar-chart-line',  'Analytika'],
                ];
                foreach ($nav_items as $p => [$icon, $label]):
                    $active = ($page === $p) ? ' active' : '';
                ?>
                <li class="nav-item">
                    <a class="nav-link<?= $active ?>" href="<?= url($p) ?>">
                        <i class="bi <?= $icon ?>"></i><?= $label ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav ms-auto gap-1">
                <li class="nav-item">
                    <a class="nav-link<?= ($page === 'bank') ? ' active' : '' ?>" href="<?= url('bank') ?>">
                        <i class="bi bi-bank2"></i>Banka
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page === 'profile') ? ' active' : '' ?>" href="<?= url('profile') ?>">
                        <i class="bi bi-person-gear"></i>Profil
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── Main content ────────────────────────────────────────────────────────── -->
<main class="container-fluid px-4 py-3">
    <?= render_flashes() ?>
