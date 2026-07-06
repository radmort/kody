<?php
/**
 * Modul: Prihlásenie používateľa
 *
 * Formulár a spracovanie prihlásenia (username + heslo).
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $row      = $username !== '' ? DB::one('SELECT id, password_hash FROM users WHERE username=?', [$username]) : null;
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['user_id'] = (int)$row['id'];
        redirect(url('dashboard'));
    }
    flash_error('Neplatné prihlasovacie údaje.');
}
?>

<div class="row justify-content-center py-5">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-arrow-in-right me-2"></i>Prihlásenie do Hermes</span>
                <a href="<?= url('register') ?>" class="btn btn-sm btn-outline-primary">Registrovať účet</a>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url('login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Používateľské meno</label>
                        <input type="text" name="username" class="form-control" required autocomplete="username"
                               value="<?= h($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Heslo</label>
                        <input type="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Prihlásiť sa</button>
                </form>
            </div>
        </div>
    </div>
</div>
