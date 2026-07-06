<?php
/**
 * Modul: Profil používateľa
 *
 * Platobné údaje dodávateľa (IBAN, IČO, DIČ) pre PDF a QR kódy,
 * vlastné SMTP nastavenia pre e-maily, zmena hesla a odhlásenie.
 */

$u = current_user();
if (!$u) {
    redirect(url('login'));
}

// ═══════════════════════════════════════════════════════════════════
//  SEKCIA: POST handlery (platobné údaje, SMTP, heslo, odhlásenie)
// ═══════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $a = $_POST['_action'] ?? '';

    if ($a === 'save_bank') {
        $iban    = preg_replace('/\s+/', '', trim($_POST['iban'] ?? ''));
        $ascii   = trim($_POST['creditor_name_ascii'] ?? '');
        $display = trim($_POST['seller_display_name'] ?? '');
        if ($iban === '' || $display === '') {
            flash_error('IBAN a názov dodávateľa sú povinné.');
        } else {
            if ($ascii === '') {
                $ascii = ascii_fold($display);
            }
            DB::exec(
                'UPDATE users SET iban=?, creditor_name_ascii=?, seller_display_name=?,
                 seller_address=?, seller_ico=?, seller_dic=?, seller_phone=?, seller_email=?, seller_web=?,
                 seller_registry=?, seller_issuer=?
                 WHERE id=?',
                [
                    $iban,
                    $ascii,
                    $display,
                    trim($_POST['seller_address'] ?? '') ?: null,
                    trim($_POST['seller_ico'] ?? '') ?: null,
                    trim($_POST['seller_dic'] ?? '') ?: null,
                    trim($_POST['seller_phone'] ?? '') ?: null,
                    trim($_POST['seller_email'] ?? '') ?: null,
                    trim($_POST['seller_web'] ?? '') ?: null,
                    trim($_POST['seller_registry'] ?? '') ?: null,
                    trim($_POST['seller_issuer'] ?? '') ?: null,
                    $u['id'],
                ]
            );
            flash_success('Platobné údaje uložené. Nové PDF budú mať aktualizované QR kódy.');
            redirect(url('profile'));
        }
    }

    if ($a === 'save_smtp') {
        $host = trim($_POST['smtp_host'] ?? '');
        $userS = trim($_POST['smtp_user'] ?? '');
        $portIn = trim($_POST['smtp_port'] ?? '');
        $fromEmail = trim($_POST['smtp_from_email'] ?? '');
        $fromName = trim($_POST['smtp_from_name'] ?? '');
        $tls = isset($_POST['smtp_use_tls']);
        $newPass = (string)($_POST['smtp_password'] ?? '');

        if ($host === '' && $userS === '') {
            DB::exec(
                'UPDATE users SET smtp_host=NULL, smtp_port=NULL, smtp_user=NULL, smtp_password=NULL,
                 smtp_from_email=NULL, smtp_from_name=NULL, smtp_use_tls=1 WHERE id=?',
                [$u['id']]
            );
            flash_success('Používa sa globálne SMTP z konfigurácie servera (Docker / .env).');
            redirect(url('profile'));
        }

        if ($host === '' || $userS === '') {
            flash_error('Server aj používateľ SMTP musia byť vyplnené spolu, alebo obe polia nechajte prázdne pre globálne SMTP.');
        } else {
            $portVal = $portIn === '' ? null : max(1, min(65535, (int)$portIn));
            if ($newPass !== '') {
                DB::exec(
                    'UPDATE users SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_password=?,
                     smtp_from_email=?, smtp_from_name=?, smtp_use_tls=? WHERE id=?',
                    [
                        $host,
                        $portVal,
                        $userS,
                        $newPass,
                        $fromEmail !== '' ? $fromEmail : null,
                        $fromName !== '' ? $fromName : null,
                        $tls ? 1 : 0,
                        $u['id'],
                    ]
                );
            } else {
                DB::exec(
                    'UPDATE users SET smtp_host=?, smtp_port=?, smtp_user=?,
                     smtp_from_email=?, smtp_from_name=?, smtp_use_tls=? WHERE id=?',
                    [
                        $host,
                        $portVal,
                        $userS,
                        $fromEmail !== '' ? $fromEmail : null,
                        $fromName !== '' ? $fromName : null,
                        $tls ? 1 : 0,
                        $u['id'],
                    ]
                );
            }
            flash_success('SMTP údaje uložené. Pri odosielaní sa použije tento účet (heslo nechajte prázdne, ak ho nemeníte).');
            redirect(url('profile'));
        }
    }

    if ($a === 'change_password') {
        $cur = (string)($_POST['current_password'] ?? '');
        $p1  = (string)($_POST['new_password'] ?? '');
        $p2  = (string)($_POST['new_password2'] ?? '');
        $full = DB::one('SELECT password_hash FROM users WHERE id=?', [$u['id']]);
        if (!$full || !password_verify($cur, $full['password_hash'])) {
            flash_error('Súčasné heslo nie je správne.');
        } elseif (strlen($p1) < 8) {
            flash_error('Nové heslo musí mať aspoň 8 znakov.');
        } elseif ($p1 !== $p2) {
            flash_error('Nové heslá sa nezhodujú.');
        } else {
            DB::exec('UPDATE users SET password_hash=? WHERE id=?', [password_hash($p1, PASSWORD_DEFAULT), $u['id']]);
            flash_success('Heslo bolo zmenené.');
            redirect(url('profile'));
        }
    }

    if ($a === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        flash_info('Boli ste odhlásený.');
        redirect(url('login'));
    }
}

$u = current_user();
?>

<div class="page-header">
    <h1><i class="bi bi-person-badge text-primary me-2"></i>Môj profil</h1>
    <p class="text-muted mb-0">Údaje príjemcu pre QR platby (EPC / PayMe) a hlavičku faktúry PDF. Bankové AIS je v menu <a href="<?= url('bank') ?>">Banka</a>.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Platobné údaje a dodávateľ na faktúre</div>
            <div class="card-body">
                <form method="post" action="<?= url('profile') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="save_bank">

                    <div class="mb-3">
                        <label class="form-label">IBAN príjemcu <span class="text-danger">*</span></label>
                        <input type="text" name="iban" class="form-control mono" required
                               value="<?= h($u['iban'] ?? '') ?>" placeholder="SK00 0000 0000 0000 0000 0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Názov dodávateľa (faktúra, UTF-8) <span class="text-danger">*</span></label>
                        <input type="text" name="seller_display_name" class="form-control" required
                               value="<?= h($u['seller_display_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meno účtu pre QR kód <span class="text-danger">*</span></label>
                        <input type="text" name="creditor_name_ascii" class="form-control mono"
                               value="<?= h($u['creditor_name_ascii'] ?? '') ?>"
                               placeholder="Bez diakritiky, podľa banky">
                        <div class="form-text">Ak necháte prázdne pri uložení, doplní sa z názvu dodávateľa (bez mäkkých znakov).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresa dodávateľa (voliteľné)</label>
                        <textarea name="seller_address" class="form-control" rows="2"
                                  placeholder="Ulica, PSČ mesto"><?= h($u['seller_address'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IČO</label>
                            <input type="text" name="seller_ico" class="form-control mono" value="<?= h($u['seller_ico'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DIČ</label>
                            <input type="text" name="seller_dic" class="form-control mono" value="<?= h($u['seller_dic'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zápis v registri (voliteľné)</label>
                        <input type="text" name="seller_registry" class="form-control" value="<?= h($u['seller_registry'] ?? '') ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telefón</label>
                            <input type="text" name="seller_phone" class="form-control" value="<?= h($u['seller_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="seller_email" class="form-control" value="<?= h($u['seller_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Web</label>
                            <input type="text" name="seller_web" class="form-control" value="<?= h($u['seller_web'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vystavil (voliteľné)</label>
                        <input type="text" name="seller_issuer" class="form-control" value="<?= h($u['seller_issuer'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Uložiť</button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Odosielanie e-mailov (SMTP)</div>
            <div class="card-body">
                <p class="text-muted small">
                    Ak necháte <strong>server</strong> a <strong>používateľa</strong> prázdne, použije sa globálne SMTP z prostredia
                    (Docker / <code>.env</code> — rovnaké premenné ako pre C++ <code>billing_reminder</code>).
                    Vyplnením polí tu môžete mať vlastné SMTP pre tento účet (prepíše globálne).
                </p>
                <form method="post" action="<?= url('profile') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="save_smtp">

                    <div class="row g-2">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SMTP server (host)</label>
                            <input type="text" name="smtp_host" class="form-control mono"
                                   value="<?= h($u['smtp_host'] ?? '') ?>" placeholder="napr. smtp.gmail.com" autocomplete="off">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="smtp_port" class="form-control mono" min="1" max="65535"
                                   value="<?= h((string)($u['smtp_port'] ?? '')) ?>" placeholder="465">
                                   <div class="form-text">zmeň len ak je potrebné, zvykom je 465</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Používateľ (login)</label>
                        <input type="text" name="smtp_user" class="form-control mono" placeholder="zvykom býva prihlasovacia adresa gmail"
                               value="<?= h($u['smtp_user'] ?? '') ?>" autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heslo aplikácie</label>
                        <input type="password" name="smtp_password" class="form-control" value=""
                               autocomplete="new-password" placeholder="Nechajte prázdne, ak heslo nemeníte">
                        <div class="form-text">Pri prvom uložení vlastného SMTP heslo zadajte. Ak necháte prázdne heslo v DB a v globálnom SMTP je heslo nastavené, použije sa globálne heslo. 
                            Teda nebude SMTP fungovať lebo bude mať zlé heslo</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Odosielateľ – e-mail</label>
                            <input type="email" name="smtp_from_email" class="form-control"
                                   value="<?= h($u['smtp_from_email'] ?? '') ?>" placeholder="rovnaké ako login">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Odosielateľ – meno</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="<?= h($u['smtp_from_name'] ?? '') ?>" placeholder="Voliteľné: meno odosielateľa/meno na maile">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="smtp_use_tls" id="smtp_use_tls" value="1"
                            <?= (!isset($u['smtp_use_tls']) || (int)$u['smtp_use_tls'] !== 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="smtp_use_tls">SSL/TLS (port 465) – vypnite len pri STARTTLS na porte 587</label>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-envelope-check me-1"></i>Uložiť SMTP</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Zmena hesla</div>
            <div class="card-body">
                <form method="post" action="<?= url('profile') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="change_password">
                    <div class="mb-2">
                        <label class="form-label">Súčasné heslo</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nové heslo (min. 8 znakov)</label>
                        <input type="password" name="new_password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nové heslo znova</label>
                        <input type="password" name="new_password2" class="form-control" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Zmeniť heslo</button>
                </form>
            </div>
        </div>
        <div class="card border-danger">
            <div class="card-body">
                <form method="post" action="<?= url('profile') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="logout">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Odhlásiť sa</button>
                </form>
            </div>
        </div>
        <div class="small text-muted mt-3">
            Do poznámky v QR (EPC / PayMe MSG) sa ukladá: číslo faktúry, variabilný symbol a suma.
            Niektoré bankové aplikácie zobrazia len časť polí alebo poznámku vôbec.
        </div>
    </div>
</div>
