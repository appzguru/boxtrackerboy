-- Boxtracker — schema
-- Canonieke bron voor de tabellen (geen CI4-migraties, zie handoff.md).
-- Idempotent: opnieuw draaien op een bestaande database doet niets stuk.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS accounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(60) NOT NULL,
    pincode VARCHAR(4) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_accounts_pincode (pincode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_account_sessions_token (token),
    KEY idx_account_sessions_account_id (account_id),
    CONSTRAINT fk_account_sessions_account
        FOREIGN KEY (account_id) REFERENCES accounts (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(120) NOT NULL,
    soort ENUM('huis', 'opslag', 'nieuw huis', 'overig') NOT NULL DEFAULT 'overig',
    actief TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_naam (naam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boxes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nummer INT UNSIGNED NOT NULL,
    token VARCHAR(8) NOT NULL,
    omschrijving TEXT NULL,
    eigenaar VARCHAR(60) NULL,
    einddoel VARCHAR(80) NULL,
    huidige_locatie VARCHAR(120) NULL,
    status ENUM('leeg', 'ingepakt', 'opgeslagen', 'geopend', 'uitgepakt') NOT NULL DEFAULT 'leeg',
    fragiel TINYINT(1) NOT NULL DEFAULT 0,
    eerst_openen TINYINT(1) NOT NULL DEFAULT 0,
    ingepakt_door VARCHAR(60) NULL,
    ingepakt_op DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_boxes_nummer (nummer),
    KEY idx_boxes_status (status),
    KEY idx_boxes_huidige_locatie (huidige_locatie),
    KEY idx_boxes_einddoel (einddoel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    box_id INT UNSIGNED NOT NULL,
    van_locatie VARCHAR(120) NULL,
    naar_locatie VARCHAR(120) NOT NULL,
    door VARCHAR(60) NOT NULL,
    op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    batch_id VARCHAR(36) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_movements_batch_box (batch_id, box_id),
    KEY idx_movements_box_id (box_id),
    CONSTRAINT fk_movements_box
        FOREIGN KEY (box_id) REFERENCES boxes (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS photos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    box_id INT UNSIGNED NOT NULL,
    bestandsnaam VARCHAR(120) NOT NULL,
    op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_photos_box_id (box_id),
    CONSTRAINT fk_photos_box
        FOREIGN KEY (box_id) REFERENCES boxes (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: de vier pincode-accounts (zie handoff.md §2, credentials.md).
-- INSERT IGNORE zodat opnieuw draaien niet crasht op de unique pincode.
INSERT IGNORE INTO accounts (naam, pincode) VALUES
    ('Edwin', '2905'),
    ('Irma', '1205'),
    ('hulp1', '0001'),
    ('hulp2', '0002');
