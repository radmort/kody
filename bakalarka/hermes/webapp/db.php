<?php
/**
 * PDO singleton + idempotentné „migrácie“ pri štarte (ensureSchema).
 * Nové inštalácie majú základ v sql/schema.sql; tu sa dopĺňajú users, user_id,
 * šablóny, bankové AIS tabuľky a chýbajúce stĺpce na starých DB.
 */
// db.php – PDO singleton for MariaDB

class DB
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /** Execute any SQL, return PDOStatement */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch all rows */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Fetch single row or null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch single scalar value */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $row = self::run($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row ? $row[0] : null;
    }

    /** Execute INSERT/UPDATE/DELETE, return affected rows */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function lastId(): string
    {
        return self::get()->lastInsertId();
    }

    public static function begin(): void  { self::get()->beginTransaction(); }
    public static function commit(): void { self::get()->commit(); }
    public static function rollback(): void { self::get()->rollBack(); }

    // ═══════════════════════════════════════════════════════════════
    //  SEKCIA: Migrácie – dopĺňanie tabuliek a stĺpcov pri štarte
    // ═══════════════════════════════════════════════════════════════

    /** Auto-create webapp-specific tables if missing */
    public static function ensureSchema(): void
    {
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS invoice_templates (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(255)     NOT NULL,
                client_id   INT UNSIGNED     NOT NULL,
                currency    CHAR(3)          NOT NULL DEFAULT 'EUR',
                send_day    TINYINT UNSIGNED NOT NULL DEFAULT 1
                            COMMENT 'Day of month to auto-generate invoice',
                due_days    INT              NOT NULL DEFAULT 14
                            COMMENT 'Days from issue to due date',
                active      TINYINT(1)       NOT NULL DEFAULT 1,
                created_at  DATETIME         NOT NULL DEFAULT NOW(),
                CONSTRAINT fk_tpl_client FOREIGN KEY (client_id)
                    REFERENCES clients(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::get()->exec("
            CREATE TABLE IF NOT EXISTS template_items (
                id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                template_id      INT UNSIGNED     NOT NULL,
                name             VARCHAR(255)     NOT NULL,
                description      TEXT,
                unit             TINYINT UNSIGNED NOT NULL DEFAULT 0
                                 COMMENT '0=ks 1=hod',
                unit_price_cents BIGINT           NOT NULL,
                vat_bp           INT              NOT NULL DEFAULT 0,
                number           BIGINT           NOT NULL DEFAULT 10
                                 COMMENT 'qty in tenths: 10=1.0',
                position         INT UNSIGNED     NOT NULL DEFAULT 1,
                CONSTRAINT fk_ti_tpl FOREIGN KEY (template_id)
                    REFERENCES invoice_templates(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Add template_id to invoices if column doesn't exist yet
        $col = self::scalar("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'invoices'
              AND COLUMN_NAME  = 'template_id'
        ");
        if (!$col) {
            self::get()->exec("
                ALTER TABLE invoices
                    ADD COLUMN template_id INT UNSIGNED NULL AFTER vs,
                    ADD CONSTRAINT fk_inv_tpl
                        FOREIGN KEY (template_id)
                        REFERENCES invoice_templates(id)
                        ON DELETE SET NULL
            ");
        }

        // Platby (manuálne alebo bank_ais po synci; external_ref = ID transakcie z banky)
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_id   INT UNSIGNED NOT NULL,
                amount_cents BIGINT       NOT NULL,
                currency     CHAR(3)      NOT NULL DEFAULT 'EUR',
                paid_at      DATE         NOT NULL,
                method       ENUM('bank','cash','card','other') NOT NULL DEFAULT 'bank',
                reference    VARCHAR(100),
                notes        TEXT,
                source       ENUM('manual','bank_ais') NOT NULL DEFAULT 'manual',
                external_ref VARCHAR(256) NULL,
                CONSTRAINT fk_pay_inv FOREIGN KEY (invoice_id)
                    REFERENCES invoices(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // email_queue table
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS email_queue (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                invoice_id    INT UNSIGNED NOT NULL,
                recipient     VARCHAR(320) NOT NULL,
                subject       VARCHAR(255) NOT NULL,
                status        ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
                attempt_count TINYINT      NOT NULL DEFAULT 0,
                sent_at       DATETIME,
                error_message TEXT,
                created_at    DATETIME     NOT NULL DEFAULT NOW(),
                CONSTRAINT fk_eq_inv FOREIGN KEY (invoice_id)
                    REFERENCES invoices(id) ON DELETE CASCADE,
                INDEX idx_eq_status  (status),
                INDEX idx_eq_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // audit_log table
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS audit_log (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                entity     VARCHAR(50)  NOT NULL,
                entity_id  INT UNSIGNED,
                action     VARCHAR(50)  NOT NULL,
                detail     JSON,
                created_at DATETIME     NOT NULL DEFAULT NOW(),
                INDEX idx_al_entity (entity, entity_id),
                INDEX idx_al_time   (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Extra indexes (safe to ignore if already exist)
        foreach ([
            "ALTER TABLE invoices  ADD INDEX idx_inv_client_id (client_id)",
            "ALTER TABLE invoices  ADD INDEX idx_inv_issue_date (issue_date)",
            "ALTER TABLE invoices  ADD INDEX idx_inv_due_date (due_date)",
            "ALTER TABLE reminder_log ADD INDEX idx_rl_client (client_id)",
        ] as $ddl) {
            try { self::get()->exec($ddl); } catch (Throwable) {}
        }

        $pfx = self::scalar("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'clients'
              AND COLUMN_NAME  = 'invoice_prefix'
        ");
        if (!$pfx) {
            self::get()->exec("
                ALTER TABLE clients
                    ADD COLUMN invoice_prefix CHAR(1) NULL
                        COMMENT '1 písmeno v čísle faktúry (R2026-0001); prázdne = prvé písmeno mena'
                    AFTER name
            ");
        }

        // ── Users (login + platobné údaje pre PDF / QR) ─────────────────────────
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS users (
                id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username              VARCHAR(64)  NOT NULL UNIQUE,
                password_hash         VARCHAR(255) NOT NULL,
                iban                  VARCHAR(34)  NOT NULL,
                creditor_name_ascii   VARCHAR(80)  NOT NULL COMMENT 'Meno účtu pre QR, bez diakritiky',
                seller_display_name   VARCHAR(255) NOT NULL COMMENT 'Názov dodávateľa na faktúre (UTF-8)',
                seller_address        TEXT         NULL,
                seller_ico            VARCHAR(32)  NULL,
                seller_dic            VARCHAR(32)  NULL,
                seller_phone          VARCHAR(64)  NULL,
                seller_email          VARCHAR(255) NULL,
                seller_web            VARCHAR(255) NULL,
                seller_registry       VARCHAR(255) NULL,
                seller_issuer         VARCHAR(255) NULL,
                created_at            DATETIME     NOT NULL DEFAULT NOW()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $uc = (int)self::scalar("SELECT COUNT(*) FROM users");
        if ($uc === 0) {
            $hash = password_hash('changeme', PASSWORD_DEFAULT);
            self::exec(
                "INSERT INTO users (id, username, password_hash, iban, creditor_name_ascii, seller_display_name)
                 VALUES (1, 'admin', ?, 'SK9511000000002943217802', 'Ing. Dusan Horvath', 'Ing. Dušan Horváth')",
                [$hash]
            );
        }

        foreach ([
            'clients' => 'AFTER id',
            'invoices' => 'AFTER id',
            'items' => 'AFTER id',
            'invoice_templates' => 'AFTER id',
        ] as $tbl => $after) {
            $has = self::scalar("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'user_id'
            ", [$tbl]);
            if (!$has) {
                try {
                    self::get()->exec("ALTER TABLE `{$tbl}` ADD COLUMN user_id INT UNSIGNED NOT NULL DEFAULT 1 {$after}");
                } catch (Throwable) {}
            }
        }

        foreach (['clients', 'invoices', 'items', 'invoice_templates'] as $tbl) {
            try {
                self::get()->exec("ALTER TABLE `{$tbl}` ADD CONSTRAINT fk_{$tbl}_user FOREIGN KEY (user_id) REFERENCES users(id)");
            } catch (Throwable) {}
        }

        $tplVsPref = (int)self::scalar("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoice_templates' AND COLUMN_NAME = 'vs_prefix'
        ");
        if ($tplVsPref) {
            try {
                self::get()->exec('ALTER TABLE invoice_templates DROP COLUMN vs_prefix');
            } catch (Throwable) {}
        }

        try {
            self::get()->exec('UPDATE clients SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
            self::get()->exec('UPDATE invoices SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
            self::get()->exec('UPDATE items SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
            self::get()->exec('UPDATE invoice_templates SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
        } catch (Throwable) {}

        // ── SBAS / PSD2 AIS – prepojenie banky a import transakcií ─────────────
        self::get()->exec("
            CREATE TABLE IF NOT EXISTS bank_connections (
                id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id               INT UNSIGNED NOT NULL,
                provider_label        VARCHAR(64)  NOT NULL DEFAULT 'default',
                account_resource_id   VARCHAR(256) NULL,
                account_iban_display    VARCHAR(42)  NULL,
                access_token_enc      TEXT         NOT NULL,
                refresh_token_enc     TEXT         NULL,
                token_expires_at      DATETIME     NULL,
                consent_expires_at    DATETIME     NULL,
                status                ENUM('active','error','disconnected') NOT NULL DEFAULT 'active',
                last_error            TEXT         NULL,
                last_sync_at          DATETIME     NULL,
                created_at            DATETIME     NOT NULL DEFAULT NOW(),
                updated_at            DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
                UNIQUE KEY uq_bank_conn_user_label (user_id, provider_label),
                INDEX idx_bc_status (status),
                CONSTRAINT fk_bc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        self::get()->exec("
            CREATE TABLE IF NOT EXISTS bank_imported_transactions (
                id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bank_connection_id   INT UNSIGNED NOT NULL,
                external_id          VARCHAR(256) NOT NULL,
                invoice_id           INT UNSIGNED NULL,
                booked_date          DATE         NOT NULL,
                amount_cents         BIGINT       NOT NULL,
                currency             CHAR(3)      NOT NULL DEFAULT 'EUR',
                match_detail         JSON         NULL,
                created_at           DATETIME     NOT NULL DEFAULT NOW(),
                UNIQUE KEY uq_bank_tx (bank_connection_id, external_id),
                CONSTRAINT fk_bit_conn FOREIGN KEY (bank_connection_id)
                    REFERENCES bank_connections(id) ON DELETE CASCADE,
                CONSTRAINT fk_bit_inv FOREIGN KEY (invoice_id)
                    REFERENCES invoices(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $paySrc = self::scalar("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'source'
        ");
        if (!$paySrc) {
            try {
                self::get()->exec("
                    ALTER TABLE payments
                        ADD COLUMN source ENUM('manual','bank_ais') NOT NULL DEFAULT 'manual' AFTER notes,
                        ADD COLUMN external_ref VARCHAR(256) NULL AFTER source
                ");
            } catch (Throwable) {}
        }

        $smtpHostCol = (int)self::scalar("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'smtp_host'
        ");
        if (!$smtpHostCol) {
            try {
                self::get()->exec("
                    ALTER TABLE users
                        ADD COLUMN smtp_host VARCHAR(255) NULL DEFAULT NULL
                            COMMENT 'Prázdne = globálne SMTP z prostredia',
                        ADD COLUMN smtp_port INT UNSIGNED NULL DEFAULT NULL,
                        ADD COLUMN smtp_user VARCHAR(255) NULL DEFAULT NULL,
                        ADD COLUMN smtp_password VARCHAR(512) NULL DEFAULT NULL,
                        ADD COLUMN smtp_from_email VARCHAR(255) NULL DEFAULT NULL,
                        ADD COLUMN smtp_from_name VARCHAR(255) NULL DEFAULT NULL,
                        ADD COLUMN smtp_use_tls TINYINT(1) NOT NULL DEFAULT 1
                ");
            } catch (Throwable) {}
        }
    }
}
