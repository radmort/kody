<?php
/**
 * Build merged Hermes JSON config (base config.json + SMTP from environment).
 *
 * Jednotný zdroj SMTP pre Docker: .env → premenné SMTP_* → tento súbor doplní runtime JSON,
 * ktorý číta billing_reminder aj invoice_pdf_ensure_cached(). C++ navyše pri štarte volá
 * apply_smtp_env_overrides() (rovnaké SMTP_*), takže stačí mať údaje v .env, netreba duplicitne v config.json.
 */
$base = getenv('HERMES_BASE_CONFIG') ?: '/etc/hermes/config.json';
$out  = getenv('HERMES_CONFIG') ?: '/tmp/hermes_runtime_config.json';
$cfg  = [];

if (is_file($base)) {
    $raw = file_get_contents($base);
    $dec = json_decode($raw, true);
    if (is_array($dec)) {
        $cfg = $dec;
    }
}

if (!isset($cfg['smtp']) || !is_array($cfg['smtp'])) {
    $cfg['smtp'] = [];
}

$map = [
    'host'       => 'SMTP_HOST',
    'port'       => 'SMTP_PORT',
    'user'       => 'SMTP_USER',
    'password'   => 'SMTP_PASSWORD',
    'from_email' => 'SMTP_FROM',
    'from_name'  => 'SMTP_FROM_NAME',
];

foreach ($map as $key => $envName) {
    $val = getenv($envName);
    if ($val !== false && $val !== '') {
        $cfg['smtp'][$key] = $val;
    }
}

$tls = getenv('SMTP_TLS');
if ($tls !== false && $tls !== '') {
    $cfg['smtp']['use_tls'] = filter_var($tls, FILTER_VALIDATE_BOOLEAN);
}

if (!isset($cfg['smtp']['port']) || $cfg['smtp']['port'] === '') {
    $cfg['smtp']['port'] = '465';
}
$cfg['smtp']['port'] = (string) $cfg['smtp']['port'];

file_put_contents($out, json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
