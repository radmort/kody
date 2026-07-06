<?php
/**
 * Hermes – front controller (jedna vstupná brána).
 *
 * 1) Načíta konfiguráciu, DB, helpery, mailer, auth.
 * 2) DB::ensureSchema() doplní chýbajúce tabuľky/stĺpce (migrácia pri štarte).
 * 3) Parametr `page` z query mapuje na súbor `pages/{page}.php` (whitelist nižšie).
 * 4) Verejné stránky: login, register. Ostatné vyžadujú session `user_id`.
 */
// index.php – Hermes front controller

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/auth.php';

// Ensure webapp-specific schema is present
try { DB::ensureSchema(); } catch (Throwable) {}

// Route
$page = preg_replace('/[^a-z_]/', '', strtolower($_GET['page'] ?? 'dashboard'));
$valid = ['dashboard', 'customers', 'invoices', 'items', 'templates', 'reminders', 'analytics', 'login', 'register', 'profile', 'bank', 'bank_oauth'];
if (!in_array($page, $valid)) {
    $page = 'dashboard';
}

$public_pages = ['login', 'register'];
if (!in_array($page, $public_pages, true) && !current_user_id()) {
    redirect(url('login'));
}
if (in_array($page, $public_pages, true) && current_user_id()) {
    redirect(url('dashboard'));
}

$page_file = __DIR__ . '/pages/' . $page . '.php';

// Capture page content
ob_start();
require $page_file;
$content = ob_get_clean();

// Render full layout
require __DIR__ . '/partials/header.php';
echo $content;
require __DIR__ . '/partials/footer.php';
