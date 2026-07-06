<?php
/**
 * Modul: Bankové prepojenie (AIS / SBAS)
 *
 * UI a POST handlery pre OAuth prepojenie banky, manuálnu synchronizáciu,
 * mock testovanie platieb a správu bankových prepojení.
 */

require_once __DIR__ . '/../lib/SbasConfig.php';
require_once __DIR__ . '/../lib/SbasBanksCatalog.php';
require_once __DIR__ . '/../lib/BankTokenVault.php';
require_once __DIR__ . '/../lib/SbasHttp.php';
require_once __DIR__ . '/../lib/SbasOAuth.php';
require_once __DIR__ . '/../lib/BankPaymentSync.php';

$u = current_user();
if (!$u) {
    redirect(url('login'));
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: POST handlery (OAuth, mock, sync, disconnect)
// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';

    if ($a === 'bank_oauth_start') {
        $pickBank = trim((string)($_POST['sbas_bank_id'] ?? ''));
        if ($pickBank === '') {
            flash_error('Vyberte banku z ponuky alebo „Vlastné URL v sbas.json“.');
            redirect(url('bank'));
        }
        if ($pickBank === 'file') {
            unset($_SESSION['sbas_catalog_bank_id']);
        } elseif (!SbasBanksCatalog::isValidBankId($pickBank)) {
            flash_error('Neplatná voľba banky. Skontrolujte config/sbas_banks_catalog.json.');
            redirect(url('bank'));
        } else {
            $_SESSION['sbas_catalog_bank_id'] = $pickBank;
        }

        $cfg = SbasConfig::load();
        if (!$cfg->enabled()) {
            flash_error('V súbore config/sbas.json nie je enabled=true alebo súbor chýba.');
            redirect(url('bank'));
        }
        if (SbasOAuth::redirectUri() === '') {
            flash_error('Nastavte premennú prostredia HERMES_PUBLIC_URL (verejná URL aplikácie) pre OAuth redirect.');
            redirect(url('bank'));
        }
        if (!BankTokenVault::canEncrypt()) {
            flash_error('Nastavte HERMES_BANK_TOKEN_KEY (šifrovanie tokenov v databáze).');
            redirect(url('bank'));
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['sbas_oauth_state'] = $state;
        $_SESSION['sbas_pkce_verifier'] = $cfg->usePkce() ? SbasOAuth::randomUrlSafe(32) : null;
        $pkce = $_SESSION['sbas_pkce_verifier'];
        redirect(SbasOAuth::buildAuthorizeUrl($cfg, $state, $pkce));
    }

    if ($a === 'bank_mock_connect') {
        $cfg = SbasConfig::load();
        if (!$cfg->mockEnabled()) {
            flash_error('Mock režim nie je zapnutý (sbas.json → mock.enabled alebo SBAS_USE_MOCK=1).');
        } elseif (!BankTokenVault::canEncrypt()) {
            flash_error('Nastavte HERMES_BANK_TOKEN_KEY.');
        } else {
            $label = $cfg->providerLabel() . '_mock';
            $enc = BankTokenVault::encrypt('mock_access');
            $ex = DB::one('SELECT id FROM bank_connections WHERE user_id=? AND provider_label=?', [$u['id'], $label]);
            if ($ex) {
                DB::exec(
                    'UPDATE bank_connections SET account_resource_id=?, account_iban_display=?, access_token_enc=?, refresh_token_enc=NULL, token_expires_at=NULL, status=?, last_error=NULL WHERE id=?',
                    ['mock', 'MOCK', $enc, 'active', (int)$ex['id']]
                );
            } else {
                DB::exec(
                    'INSERT INTO bank_connections (user_id, provider_label, account_resource_id, account_iban_display, access_token_enc, status) VALUES (?,?,?,?,?,?)',
                    [$u['id'], $label, 'mock', 'MOCK', $enc, 'active']
                );
            }
            flash_success('Testovacie bankové prepojenie vytvorené. Spustite synchronizáciu alebo cron.');
        }
        redirect(url('bank'));
    }

    if ($a === 'bank_sync_now') {
        $cid = (int)($_POST['connection_id'] ?? 0);
        $row = DB::one('SELECT id FROM bank_connections WHERE id=? AND user_id=? AND status=?', [$cid, $u['id'], 'active']);
        if (!$row) {
            flash_error('Neplatné prepojenie.');
        } else {
            $r = BankPaymentSync::syncConnection((int)$row['id']);
            $r['ok']
                ? flash_success($r['message'] . ' Označených faktúr: ' . (int)$r['marked'] . '.')
                : flash_error($r['message']);
        }
        redirect(url('bank'));
    }

    if ($a === 'bank_disconnect') {
        $cid = (int)($_POST['connection_id'] ?? 0);
        DB::exec(
            "UPDATE bank_connections SET status='disconnected', last_error=NULL WHERE id=? AND user_id=?",
            [$cid, $u['id']]
        );
        flash_info('Bankové prepojenie bolo odpojené (tokeny zostávajú v DB v šifre – pri opätovnom OAuth sa prepíšu).');
        redirect(url('bank'));
    }

    if ($a === 'bank_mock_pay_all') {
        $cfg = SbasConfig::load();
        if (!$cfg->mockEnabled()) {
            flash_error('Mock režim nie je zapnutý.');
            redirect(url('bank'));
        }
        $mockConn = DB::one(
            "SELECT id FROM bank_connections WHERE user_id=? AND account_resource_id='mock' AND status='active'",
            [$u['id']]
        );
        if (!$mockConn) {
            flash_error('Najprv vytvorte mock bankové prepojenie (tlačidlo „Mock účet").');
            redirect(url('bank'));
        }
        $r = BankPaymentSync::syncConnection((int)$mockConn['id']);
        $r['ok']
            ? flash_success('Mock sync: označených faktúr ako zaplatených: ' . (int)$r['marked'] . '.')
            : flash_error($r['message']);
        redirect(url('bank'));
    }

    if ($a === 'bank_mock_pay_selected') {
        $cfg = SbasConfig::load();
        if (!$cfg->mockEnabled()) {
            flash_error('Mock režim nie je zapnutý.');
            redirect(url('bank'));
        }
        $mockConn = DB::one(
            "SELECT id FROM bank_connections WHERE user_id=? AND account_resource_id='mock' AND status='active'",
            [$u['id']]
        );
        if (!$mockConn) {
            flash_error('Najprv vytvorte mock bankové prepojenie.');
            redirect(url('bank'));
        }
        $ids = array_map('intval', (array)($_POST['invoice_ids'] ?? []));
        if ($ids === []) {
            flash_error('Nevybrali ste žiadne faktúry.');
            redirect(url('bank'));
        }
        $r = BankPaymentSync::syncMockSelected((int)$u['id'], (int)$mockConn['id'], $ids);
        $r['ok']
            ? flash_success('Mock sync (vybrané): označených faktúr: ' . (int)$r['marked'] . '.')
            : flash_error($r['message']);
        redirect(url('bank'));
    }

    if ($a === 'bank_mock_custom_tx') {
        $cfg = SbasConfig::load();
        if (!$cfg->mockEnabled()) {
            flash_error('Mock režim nie je zapnutý.');
            redirect(url('bank'));
        }
        $mockConn = DB::one(
            "SELECT id FROM bank_connections WHERE user_id=? AND account_resource_id='mock' AND status='active'",
            [$u['id']]
        );
        if (!$mockConn) {
            flash_error('Najprv vytvorte mock bankové prepojenie.');
            redirect(url('bank'));
        }
        $txAmount = trim((string)($_POST['tx_amount'] ?? ''));
        $txVs = trim((string)($_POST['tx_vs'] ?? ''));
        $txCurrency = trim((string)($_POST['tx_currency'] ?? 'EUR'));
        if ($txAmount === '' || $txVs === '') {
            flash_error('Vyplňte sumu a variabilný symbol.');
            redirect(url('bank'));
        }
        $r = BankPaymentSync::syncMockCustom((int)$u['id'], (int)$mockConn['id'], $txAmount, $txVs, $txCurrency);
        $r['ok']
            ? flash_success('Custom transakcia spracovaná. Zhoda s faktúrami: ' . (int)$r['marked'] . '.')
            : flash_error($r['message']);
        redirect(url('bank'));
    }
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: Načítanie dát pre zobrazenie
// ═══════════════════════════════════════════════════════════════════

$sbasCfg       = SbasConfig::load();
$catalogBanks  = SbasBanksCatalog::banksForSelect();
$bankRows = DB::all(
    'SELECT * FROM bank_connections WHERE user_id=? ORDER BY id DESC',
    [$u['id']]
);
?>

<?php
// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: HTML – zobrazenie stránky
// ═══════════════════════════════════════════════════════════════════
?>

<div class="page-header">
    <h1><i class="bi bi-bank2 text-primary me-2"></i>Bankové prepojenie</h1>
    <p class="text-muted mb-0">AIS (výpis účtu) podľa SBAS / PSD2 – párovanie platieb s faktúrami. Údaje príjemcu (IBAN) upravíte v <a href="<?= url('profile') ?>">profile</a>.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-primary border-opacity-25">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg text-primary"></i>
                OAuth a synchronizácia
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Automatická kontrola príchozích platieb podľa <strong>variabilného symbolu</strong> a sumy na faktúre.
                    Štandard <abbr title="Slovak Banking API Standard">SBAS</abbr> je rámec SBA; konkrétne URL a registráciu TPP získate u svojej banky.
                </p>
                <?php if (!$sbasCfg->enabled() && !$sbasCfg->mockEnabled()): ?>
                    <div class="alert alert-secondary mb-2 py-2 small mb-0">
                        Zapnite integráciu súborom <code class="mono">config/sbas.json</code> (kópia z
                        <code class="mono">config/sbas.example.json</code>) a nastavte
                        <code class="mono">enabled: true</code>, prípadne mock pre testy.
                    </div>
                <?php else: ?>
                    <?php if (SbasOAuth::redirectUri() === ''): ?>
                        <div class="alert alert-warning py-2 small">Nastavte <code class="mono">HERMES_PUBLIC_URL</code> (napr. <code class="mono">https://vas-server.sk</code>).</div>
                    <?php endif; ?>
                    <?php if (!BankTokenVault::canEncrypt()): ?>
                        <div class="alert alert-warning py-2 small">Nastavte <code class="mono">HERMES_BANK_TOKEN_KEY</code> na dlhý náhodný reťazec.</div>
                    <?php endif; ?>
                    <?php if ($sbasCfg->enabled() && BankTokenVault::canEncrypt() && SbasOAuth::redirectUri() !== ''): ?>
                        <form method="post" action="<?= url('bank') ?>" class="row g-2 align-items-end mb-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="bank_oauth_start">
                            <div class="col-md-6 col-lg-5">
                                <label class="form-label small text-muted mb-0">Banka (predvyplnené AIS/OAuth URL)</label>
                                <select name="sbas_bank_id" class="form-select form-select-sm" required>
                                    <option value="">— vyberte —</option>
                                    <option value="file">Vlastné URL (iba config/sbas.json)</option>
                                    <?php foreach ($catalogBanks as $cb): ?>
                                        <option value="<?= h($cb['id']) ?>"><?= h($cb['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-link-45deg me-1"></i>Prepojiť (OAuth)
                                </button>
                            </div>
                        </form>
                        <p class="small text-muted mb-2">
                            Ďalšie slovenské banky pridáte do <code class="mono">config/sbas_banks_catalog.json</code>
                            (rovnaký formát ako položka „vub“). <code class="mono">client_id</code> / tajomstvo ostávajú v <code class="mono">sbas.json</code> alebo <code class="mono">.env</code>.
                        </p>
                    <?php endif; ?>
                    <?php if ($sbasCfg->mockEnabled() && BankTokenVault::canEncrypt()): ?>
                        <form method="post" action="<?= url('bank') ?>" class="d-inline ms-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="bank_mock_connect">
                            <button type="submit" class="btn btn-outline-secondary mb-2">Mock účet (vývoj)</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($bankRows !== []): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Profil</th><th>Účet</th><th>Stav</th><th>Posl. sync</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($bankRows as $br): ?>
                                    <tr>
                                        <td class="mono small"><?= h($br['provider_label']) ?></td>
                                        <td class="mono small"><?= h($br['account_iban_display'] ?: $br['account_resource_id'] ?: '—') ?></td>
                                        <td>
                                            <?php if ($br['status'] === 'active'): ?>
                                                <span class="badge text-bg-success">aktívne</span>
                                            <?php elseif ($br['status'] === 'error'): ?>
                                                <span class="badge text-bg-danger" title="<?= h((string)$br['last_error']) ?>">chyba</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">odpojené</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= h($br['last_sync_at'] ?: '—') ?></td>
                                        <td class="text-end text-nowrap">
                                            <?php if ($br['status'] === 'active'): ?>
                                                <form method="post" action="<?= url('bank') ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="_action" value="bank_sync_now">
                                                    <input type="hidden" name="connection_id" value="<?= (int)$br['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">Sync</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="<?= url('bank') ?>" class="d-inline" onsubmit="return confirm('Odpojiť túto banku?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="bank_disconnect">
                                                <input type="hidden" name="connection_id" value="<?= (int)$br['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Odpojiť</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($sbasCfg->mockEnabled()): ?>
                            <p class="small text-muted mt-2 mb-0">
                                Mock transakcie definujte v <code class="mono">config/sbas.json</code> v poli <code class="mono">mock.transactions</code>.
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="small text-muted mb-0">Žiadne uložené prepojenie. Po OAuth sa zobrazí v zozname.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <p class="small text-muted mt-3 mb-0">
            Cron <code class="mono">bank_sync.php</code> v kontajneri reminder kontroluje platby podľa rozvrhu v <code class="mono">scripts/crontab</code>.
        </p>

<?php
$hasMockConn = false;
foreach ($bankRows as $br) {
    if (($br['account_resource_id'] ?? '') === 'mock' && $br['status'] === 'active') {
        $hasMockConn = true;
        break;
    }
}

if ($sbasCfg->mockEnabled() && $hasMockConn):
    $unpaidInvoices = DB::all(
        "SELECT i.id, i.invoice_number, i.vs, i.currency, i.status, i.due_date,
                c.name AS client_name
         FROM invoices i
         LEFT JOIN clients c ON c.id = i.client_id
         WHERE i.user_id = ? AND i.status IN ('unpaid','overdue')
         ORDER BY i.due_date ASC, i.id ASC",
        [$u['id']]
    );
?>
        <div class="card border-warning border-opacity-25 mt-4">
            <div class="card-header d-flex align-items-center gap-2 bg-warning bg-opacity-10">
                <i class="bi bi-bug text-warning"></i>
                Mock testovanie platieb
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Simulácia bankových platieb bez reálneho účtu. Automaticky sa vygenerujú CRDT transakcie
                    so správnym VS a sumou, ktoré prejdú cez matching engine rovnako ako reálne platby.
                </p>

                <?php if ($unpaidInvoices === []): ?>
                    <div class="alert alert-info py-2 small mb-0">
                        Žiadne nezaplatené faktúry. Vytvorte faktúru so stavom „Nezaplatená" na otestovanie.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= url('bank') ?>" class="mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="bank_mock_pay_all">
                        <button type="submit" class="btn btn-warning"
                                onclick="return confirm('Označiť všetkých <?= count($unpaidInvoices) ?> nezaplatených faktúr ako zaplatené?');">
                            <i class="bi bi-check-all me-1"></i>Zaplatiť všetky (<?= count($unpaidInvoices) ?>)
                        </button>
                    </form>

                    <form method="post" action="<?= url('bank') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="bank_mock_pay_selected">
                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:1rem"><input type="checkbox" class="form-check-input" id="mockSelectAll"></th>
                                        <th>Číslo</th>
                                        <th>Klient</th>
                                        <th>VS</th>
                                        <th>Suma</th>
                                        <th>Stav</th>
                                        <th>Splatnosť</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($unpaidInvoices as $inv):
                                    $total = BankPaymentSync::invoiceTotalCents((int)$inv['id']);
                                ?>
                                    <tr>
                                        <td><input type="checkbox" name="invoice_ids[]" value="<?= (int)$inv['id'] ?>" class="form-check-input mock-inv-cb"></td>
                                        <td class="mono small"><?= h($inv['invoice_number']) ?></td>
                                        <td class="small"><?= h($inv['client_name'] ?? '—') ?></td>
                                        <td class="mono small"><?= h($inv['vs'] ?? '—') ?></td>
                                        <td class="mono small text-end"><?= money($total, $inv['currency'] ?? 'EUR') ?></td>
                                        <td><?= status_badge($inv['status']) ?></td>
                                        <td class="small"><?= h($inv['due_date'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-check2-square me-1"></i>Zaplatiť vybrané
                        </button>
                    </form>

                    <script>
                    document.getElementById('mockSelectAll')?.addEventListener('change', function() {
                        document.querySelectorAll('.mock-inv-cb').forEach(cb => cb.checked = this.checked);
                    });
                    </script>
                <?php endif; ?>

                <hr class="my-3">
                <h6 class="text-muted"><i class="bi bi-pencil-square me-1"></i>Vlastná mock transakcia</h6>
                <p class="small text-muted mb-2">
                    Pre testovanie edge cases: nesprávna suma, nesprávny VS, čiastočná platba, iná mena.
                </p>
                <form method="post" action="<?= url('bank') ?>" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="bank_mock_custom_tx">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Suma</label>
                        <input type="text" name="tx_amount" class="form-control form-control-sm" placeholder="120.00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0">VS / text platby</label>
                        <input type="text" name="tx_vs" class="form-control form-control-sm" placeholder="VF20260001" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Mena</label>
                        <select name="tx_currency" class="form-select form-select-sm">
                            <option value="EUR" selected>EUR</option>
                            <option value="CZK">CZK</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-send me-1"></i>Odoslať
                        </button>
                    </div>
                </form>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>
