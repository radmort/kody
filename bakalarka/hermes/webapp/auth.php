<?php
/**
 * Modul: Autentifikácia a session
 *
 * Správa prihláseného používateľa – načítanie ID a profilu zo session.
 * Multi-tenant: každý používateľ vidí len svoje dáta.
 */

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user(): ?array
{
    $id = current_user_id();
    if (!$id) {
        return null;
    }

    return DB::one('SELECT id, username, iban, creditor_name_ascii, seller_display_name,
        seller_address, seller_ico, seller_dic, seller_phone, seller_email, seller_web,
        seller_registry, seller_issuer,
        smtp_host, smtp_port, smtp_user, smtp_from_email, smtp_from_name, smtp_use_tls
        FROM users WHERE id=?', [$id]);
}
