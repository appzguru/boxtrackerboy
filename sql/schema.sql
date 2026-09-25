-- Boxtracker v2 — schema
-- Canonieke bron voor de tabellen (geen CI4-migraties, zie handoff.md §5).
-- Idempotent: opnieuw draaien op een bestaande database doet niets stuk.
-- v1 (familie, boxtracker.minisaas.nl) heeft een eigen schema op tag familie-v1.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eenmalige tokens voor e-mail bevestigen en wachtwoord resetten. Alleen de
-- sha256-hash staat hier; het token zelf zit alleen in de mail.
CREATE TABLE IF NOT EXISTS user_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    soort ENUM('verify', 'reset') NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_tokens_hash (token_hash),
    KEY idx_user_tokens_user (user_id),
    CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verhuizingen (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(80) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_verhuizingen_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    active_verhuizing_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sessions_token (token),
    KEY idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_sessions_verhuizing FOREIGN KEY (active_verhuizing_id) REFERENCES verhuizingen (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rol ENUM('admin', 'helper') NOT NULL DEFAULT 'helper',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_memberships (verhuizing_id, user_id),
    KEY idx_memberships_user (user_id),
    CONSTRAINT fk_memberships_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE,
    CONSTRAINT fk_memberships_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invites (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    token CHAR(32) NOT NULL,
    rol ENUM('admin', 'helper') NOT NULL DEFAULT 'helper',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_by INT UNSIGNED NULL,
    used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invites_token (token),
    KEY idx_invites_verhuizing (verhuizing_id),
    CONSTRAINT fk_invites_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE,
    CONSTRAINT fk_invites_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_invites_user FOREIGN KEY (used_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Handjes-QR (§3.3): de QR-code zelf is kort geldig, de gast-sessie die je ermee
-- krijgt `access_days` dagen.
CREATE TABLE IF NOT EXISTS guest_passes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    code CHAR(32) NOT NULL,
    rol ENUM('helper', 'sjouwer') NOT NULL DEFAULT 'sjouwer',
    access_days TINYINT UNSIGNED NOT NULL DEFAULT 1,
    code_expires_at DATETIME NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guest_passes_code (code),
    CONSTRAINT fk_guest_passes_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE,
    CONSTRAINT fk_guest_passes_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guest_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_pass_id INT UNSIGNED NULL,
    verhuizing_id INT UNSIGNED NOT NULL,
    rol ENUM('helper', 'sjouwer') NOT NULL,
    naam VARCHAR(60) NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guest_sessions_token (token),
    KEY idx_guest_sessions_verhuizing (verhuizing_id),
    CONSTRAINT fk_guest_sessions_pass FOREIGN KEY (guest_pass_id) REFERENCES guest_passes (id) ON DELETE SET NULL,
    CONSTRAINT fk_guest_sessions_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    naam VARCHAR(120) NOT NULL,
    soort ENUM('huis', 'opslag', 'nieuw huis', 'overig') NOT NULL DEFAULT 'overig',
    actief TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_naam (verhuizing_id, naam),
    CONSTRAINT fk_locations_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boxes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
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
    UNIQUE KEY uq_boxes_nummer (verhuizing_id, nummer),
    UNIQUE KEY uq_boxes_token (token),
    KEY idx_boxes_status (verhuizing_id, status),
    KEY idx_boxes_huidige_locatie (verhuizing_id, huidige_locatie),
    KEY idx_boxes_einddoel (verhuizing_id, einddoel),
    CONSTRAINT fk_boxes_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- movements en photos hangen via box_id aan een verhuizing, maar krijgen
-- verhuizing_id ook zelf: zo kan elke query er direct op scopen (§6).
CREATE TABLE IF NOT EXISTS movements (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    box_id INT UNSIGNED NOT NULL,
    van_locatie VARCHAR(120) NULL,
    naar_locatie VARCHAR(120) NOT NULL,
    door VARCHAR(60) NOT NULL,
    op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    batch_id VARCHAR(36) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_movements_batch_box (batch_id, box_id),
    KEY idx_movements_box_id (box_id),
    KEY idx_movements_verhuizing (verhuizing_id, op),
    CONSTRAINT fk_movements_box FOREIGN KEY (box_id) REFERENCES boxes (id) ON DELETE CASCADE,
    CONSTRAINT fk_movements_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS photos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    box_id INT UNSIGNED NOT NULL,
    bestandsnaam VARCHAR(120) NOT NULL,
    op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_photos_box_id (box_id),
    CONSTRAINT fk_photos_box FOREIGN KEY (box_id) REFERENCES boxes (id) ON DELETE CASCADE,
    CONSTRAINT fk_photos_verhuizing FOREIGN KEY (verhuizing_id) REFERENCES verhuizingen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
