<?php
/**
 * Konstanty z prostredia (Docker / .env). DB_* = MariaDB; SMTP_* = globálne e-maily (web aj billing_reminder);
 * HERMES_PUBLIC_URL = základ URL pre OAuth návrat (banka); HERMES_BANK_TOKEN_KEY mimo tohto súboru.
 */
// config.php – Hermes Web App configuration

define('DB_HOST',     getenv('DB_HOST')     ?: 'mariadb');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_NAME',     getenv('DB_NAME')     ?: 'hermes_db');
define('DB_USER',     getenv('DB_USER')     ?: 'hermes');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

define('SMTP_HOST',      getenv('SMTP_HOST')      ?: '');
define('SMTP_PORT',      (int)(getenv('SMTP_PORT') ?: 465));
define('SMTP_USER',      getenv('SMTP_USER')      ?: '');
define('SMTP_PASSWORD',  getenv('SMTP_PASSWORD')  ?: '');
define('SMTP_FROM',      getenv('SMTP_FROM')      ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Hermes');
define('SMTP_TLS',       (getenv('SMTP_TLS') ?: 'true') === 'true');

define('APP_NAME', 'Hermes');
define('BASE_URL',  '/');

/** Zhodné s app.reminder_* v config.json pre billing_reminder (C++) – ručné e-maily z faktúry. */
// Počet dní po vystavení, po ktorých sa posiela upomienka namiesto oznámenia
define('REMINDER_DAYS_AFTER_ISSUE', max(0, (int)(getenv('REMINDER_DAYS_AFTER_ISSUE') ?: 7)));
// Minimálny počet dní medzi dvoma upomienkami (ochrana pred spamom)
define('REMINDER_MIN_DAYS_BETWEEN', max(1, (int)(getenv('REMINDER_MIN_DAYS_BETWEEN') ?: 2)));

/** Verejná URL aplikácie (bez koncovej lomky) – OAuth redirect pre bankové AIS (SBAS). */
define('HERMES_PUBLIC_URL', rtrim((string)(getenv('HERMES_PUBLIC_URL') ?: ''), '/'));
