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

-- Bedrijven (whitelabel, whitelabel-plan.md): subdomein = het deel vóór .boxtracker.nl.
-- De huisstijl staat niet hier maar als bestanden in public/merken/<subdomein>/.
CREATE TABLE IF NOT EXISTS bedrijven (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(120) NOT NULL,
    subdomein VARCHAR(40) NOT NULL,
    status ENUM('actief', 'geblokkeerd') NOT NULL DEFAULT 'actief',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bedrijven_subdomein (subdomein)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- bedrijf_id NULL = particuliere verhuizing (klant-app op app.boxtracker.nl).
CREATE TABLE IF NOT EXISTS verhuizingen (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(80) NOT NULL,
    bedrijf_id INT UNSIGNED NULL,
    verhuisdatum DATE NULL,
    adres_van VARCHAR(160) NULL,
    adres_naar VARCHAR(160) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verhuizingen_bedrijf (bedrijf_id, verhuisdatum),
    CONSTRAINT fk_verhuizingen_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_verhuizingen_bedrijf FOREIGN KEY (bedrijf_id) REFERENCES bedrijven (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token CHAR(64) NOT NULL,
    active_verhuizing_id INT UNSIGNED NULL,
    meekijk_verhuizing_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sessions_token (token),
    KEY idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_sessions_verhuizing FOREIGN KEY (active_verhuizing_id) REFERENCES verhuizingen (id) ON DELETE SET NULL,
    CONSTRAINT fk_sessions_meekijk FOREIGN KEY (meekijk_verhuizing_id) REFERENCES verhuizingen (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memberships (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    verhuizing_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rol ENUM('admin', 'helper', 'sjouwer') NOT NULL DEFAULT 'helper',
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

-- Vaste medewerkers van een bedrijf (whitelabel). Een medewerker is een gewoon account.
-- planner en sales zijn admin in álle verhuizingen van hun bedrijf, inpakker en sjouwer
-- alleen in de verhuizingen waar ze via memberships aan zijn toegewezen.
-- Voor de pilot hoort een account bij hooguit één bedrijf (uq_bedrijf_medewerkers_user).
CREATE TABLE IF NOT EXISTS bedrijf_medewerkers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bedrijf_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rol ENUM('planner', 'sales', 'inpakker', 'sjouwer') NOT NULL,
    actief TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bedrijf_medewerkers_user (user_id),
    KEY idx_bedrijf_medewerkers_bedrijf (bedrijf_id),
    CONSTRAINT fk_bedrijf_medewerkers_bedrijf FOREIGN KEY (bedrijf_id) REFERENCES bedrijven (id) ON DELETE CASCADE,
    CONSTRAINT fk_bedrijf_medewerkers_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uitnodiging om medewerker van een bedrijf te worden (7 dagen, eenmalig). Landt op het subdomein.
CREATE TABLE IF NOT EXISTS medewerker_uitnodigingen (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bedrijf_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    rol ENUM('planner', 'sales', 'inpakker', 'sjouwer') NOT NULL,
    token CHAR(32) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_by INT UNSIGNED NULL,
    used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medewerker_uitnodigingen_token (token),
    KEY idx_medewerker_uitnodigingen_bedrijf (bedrijf_id),
    CONSTRAINT fk_medewerker_uitnodigingen_bedrijf FOREIGN KEY (bedrijf_id) REFERENCES bedrijven (id) ON DELETE CASCADE,
    CONSTRAINT fk_medewerker_uitnodigingen_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_medewerker_uitnodigingen_user FOREIGN KEY (used_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global-admins (beheer op app.boxtracker.nl/beheer). Alleen met de hand vullen.
CREATE TABLE IF NOT EXISTS platform_admins (
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_platform_admins_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wat een global-admin deed: bedrijf aanmaken/blokkeren, uitnodigen, meekijken.
-- Geen foreign keys: het log blijft staan als een bedrijf of verhuizing weg is.
CREATE TABLE IF NOT EXISTS beheer_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    bedrijf_id INT UNSIGNED NULL,
    verhuizing_id INT UNSIGNED NULL,
    actie VARCHAR(60) NOT NULL,
    detail VARCHAR(200) NULL,
    op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_beheer_log_bedrijf (bedrijf_id, op)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
