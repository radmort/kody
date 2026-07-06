-- =============================================================================
-- Hermes – Full Database Schema  (MariaDB 11.4 / InnoDB)
-- Run automatically by docker-entrypoint-initdb.d on first start.
--
-- Multi-tenant layer (users, user_id na riadkoch, šablóny, bankové tabuľky)
-- is populated(doplni) during the first HTTP request via webapp/db.php → DB::ensureSchema().
-- =============================================================================

-- ── clients ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clients (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(255) NOT NULL,
    address TEXT,
    email   VARCHAR(255) NOT NULL,
    phone   VARCHAR(50),
    ico     CHAR(8),
    dic     CHAR(10),
    iban    VARCHAR(34),
    CONSTRAINT chk_ico CHECK (ico IS NULL OR ico REGEXP '^[1-9][0-9]{7}$'),
    CONSTRAINT chk_dic CHECK (dic IS NULL OR dic REGEXP '^[0-9]{10}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── invoices ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoices (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id      INT UNSIGNED,
    invoice_number VARCHAR(50)  NOT NULL UNIQUE,
    status         ENUM('draft','unpaid','paid','cancelled','overdue')
                                NOT NULL DEFAULT 'unpaid',
    currency       CHAR(3)      NOT NULL DEFAULT 'EUR',
    issue_date     DATE         NOT NULL DEFAULT (CURDATE()),
    due_date       DATE         NOT NULL,
    vs             VARCHAR(50)  NULL COMMENT 'Variabilny symbol',
    CONSTRAINT fk_inv_client FOREIGN KEY (client_id)
        REFERENCES clients(id) ON DELETE SET NULL,
    INDEX idx_inv_status_due (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── items ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(255)         NOT NULL,
    description       TEXT,
    unit              TINYINT UNSIGNED     NOT NULL COMMENT '0=ks 1=hod',
    unit_price_cents  BIGINT               NOT NULL,
    vat_bp            INT                  NOT NULL COMMENT 'basis points e.g. 2000=20%',
    number            BIGINT               NOT NULL COMMENT 'qty in tenths e.g. 10=1.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── invoice_items ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS invoice_items (
    invoice_id INT UNSIGNED NOT NULL,
    item_id    INT UNSIGNED NOT NULL,
    position   INT UNSIGNED NOT NULL,
    PRIMARY KEY (invoice_id, position),
    UNIQUE KEY uniq_invoice_item (invoice_id, item_id),
    CONSTRAINT chk_pos CHECK (position > 0),
    CONSTRAINT fk_iitems_inv  FOREIGN KEY (invoice_id)
        REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_iitems_item FOREIGN KEY (item_id)
        REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── reminder_log  (auto-created by reminder binary too, but here for clarity) ─
CREATE TABLE IF NOT EXISTS reminder_log (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id    INT UNSIGNED NOT NULL,
    client_id     INT UNSIGNED NOT NULL,
    sent_at       DATETIME     NOT NULL DEFAULT NOW(),
    success       TINYINT(1)   NOT NULL,
    error_message TEXT,
    CONSTRAINT fk_rl_inv FOREIGN KEY (invoice_id)
        REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_rl_cli FOREIGN KEY (client_id)
        REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_rl_invoice (invoice_id),
    INDEX idx_rl_month   (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Dev seed data ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO clients (name, email, ico, dic, iban) VALUES
    ('Hanss, s.r.o.', 'petergerek001@gmail.com', '51853469', '2120810252',
     'SK2120810252');

INSERT IGNORE INTO invoices (client_id, invoice_number, status, currency,
                              issue_date, due_date, vs)
VALUES
    (1, '2025-0001', 'unpaid', 'EUR', '2025-07-01',
     DATE_ADD(CURDATE(), INTERVAL 7  DAY), 'VS:20250001'),
    (1, '2025-0002', 'unpaid', 'EUR', '2025-08-20',
     DATE_ADD(CURDATE(), INTERVAL -3 DAY), 'VS:20250002');

-- ── payments ──────────────────────────────────────────────────────────────────
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── email_queue ───────────────────────────────────────────────────────────────
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── bank AIS (SBAS / PSD2) – tabuľky dopĺňa aj webapp/db.php ensureSchema() ───
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
    INDEX idx_bc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    UNIQUE KEY uq_bank_tx (bank_connection_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── audit_log ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity     VARCHAR(50)  NOT NULL,
    entity_id  INT UNSIGNED,
    action     VARCHAR(50)  NOT NULL,
    detail     JSON,
    created_at DATETIME     NOT NULL DEFAULT NOW(),
    INDEX idx_al_entity (entity, entity_id),
    INDEX idx_al_time   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
