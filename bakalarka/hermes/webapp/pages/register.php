<?php
/**
 * Modul: Registrácia nového používateľa
 *
 * Formulár registrácie s validáciou IBAN, hesla a obchodného mena.
 * Po registrácii sa používateľ automaticky prihlási.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $pass1     = (string)($_POST['password'] ?? '');
    $pass2     = (string)($_POST['password2'] ?? '');
    $display   = trim($_POST['seller_display_name'] ?? '');
    $iban      = preg_replace('/\s+/', '', strtoupper(trim($_POST['iban'] ?? '')));
    $ascii     = trim($_POST['creditor_name_ascii'] ?? '');

    if ($username === '' || strlen($username) < 2) {
        flash_error('Používateľské meno musí mať aspoň 2 znaky.');
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        flash_error('Meno môže obsahovať len písmená, číslice, bodku, pomlčku a podčiarkovník.');
    } elseif (strlen($pass1) < 8) {
        flash_error('Heslo musí mať aspoň 8 znakov.');
    } elseif ($pass1 !== $pass2) {
        flash_error('Heslá sa nezhodujú.');
    } elseif ($display === '') {
        flash_error('Vyplňte názov dodávateľa (obchodné meno).');
    } elseif (!preg_match('/^SK[0-9]{22}$/', $iban)) {
        flash_error('IBAN musí byť slovenský formát SK + 22 číslic (24 znakov spolu).');
    } else {
        if ($ascii === '') {
            $ascii = ascii_fold($display);
        }
        $exists = DB::scalar('SELECT COUNT(*) FROM users WHERE username=?', [$username]);
        if ($exists) {
            flash_error('Toto používateľské meno je už obsadené.');
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            try {
                DB::exec(
                    'INSERT INTO users (username, password_hash, iban, creditor_name_ascii, seller_display_name)
                     VALUES (?,?,?,?,?)',
                    [$username, $hash, $iban, $ascii, $display]
                );
                $newId = (int)DB::lastId();
                $_SESSION['user_id'] = $newId;
                flash_success('Účet bol vytvorený. Môžete pridať zákazníkov a faktúry.');
                redirect(url('dashboard'));
            } catch (Throwable $e) {
                flash_error('Registrácia zlyhala: ' . $e->getMessage());
            }
        }
    }
}
?>

<div class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-plus me-2"></i>Nový účet</span>
                <a href="<?= url('login') ?>" class="btn btn-sm btn-outline-secondary">Späť na prihlásenie</a>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('register') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Používateľské meno</label>
                        <input type="text" name="username" class="form-control" required autocomplete="username"
                               pattern="[a-zA-Z0-9._-]{2,64}"
                               value="<?= h($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Heslo (min. 8 znakov)</label>
                            <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Heslo znova</label>
                            <input type="password" name="password2" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Obchodné meno / názov dodávateľa</label>
                        <input type="text" name="seller_display_name" class="form-control" required
                               value="<?= h($_POST['seller_display_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IBAN (váš účet príjemcu)</label>
                        <input type="text" name="iban" class="form-control mono" required
                               placeholder="SK00 0000 0000 0000 0000 0000"
                               value="<?= h($_POST['iban'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meno účtu pre banku (bez diakritiky)</label>
                        <input type="text" name="creditor_name_ascii" class="form-control mono"
                               placeholder="Nechajte prázdne – doplní sa z názvu dodávateľa"
                               value="<?= h($_POST['creditor_name_ascii'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrovať sa</button>
                </form>
            </div>
        </div>
    </div>
</div>
