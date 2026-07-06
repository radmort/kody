<?php
/**
 * Modul: Šifrovanie bankových tokenov
 *
 * AES-256-GCM šifrovanie access/refresh tokenov v databáze.
 * Kľúč sa nastavuje cez premennú prostredia HERMES_BANK_TOKEN_KEY.
 *
 * Hlavné metódy:
 *   encrypt() – zašifrovanie tokenu pred uložením do DB
 *   decrypt() – dešifrovanie tokenu pri čítaní z DB
 */

class BankTokenVault
{
    public static function keyBinary(): ?string
    {
        $k = getenv('HERMES_BANK_TOKEN_KEY');
        if ($k === false || $k === '') {
            return null;
        }

        return hash('sha256', $k, true);
    }

    public static function canEncrypt(): bool
    {
        return self::keyBinary() !== null && function_exists('openssl_encrypt');
    }

    public static function encrypt(string $plain): string
    {
        $key = self::keyBinary();
        if ($key === null) {
            throw new RuntimeException('Chýba HERMES_BANK_TOKEN_KEY – tokeny sa nedajú bezpečne uložiť.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ct === false) {
            throw new RuntimeException('Šifrovanie tokenu zlyhalo.');
        }

        return base64_encode($iv . $tag . $ct);
    }

    public static function decrypt(string $stored): string
    {
        $key = self::keyBinary();
        if ($key === null) {
            throw new RuntimeException('Chýba HERMES_BANK_TOKEN_KEY.');
        }
        $raw = base64_decode($stored, true);
        if ($raw === false || strlen($raw) < 28) {
            throw new RuntimeException('Poškodený uložený token.');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct = substr($raw, 28);
        $pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($pt === false) {
            throw new RuntimeException('Dešifrovanie tokenu zlyhalo.');
        }

        return $pt;
    }
}
