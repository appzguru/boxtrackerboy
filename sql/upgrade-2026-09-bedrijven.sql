-- Eenmalige upgrade van een bestaande v2-database naar het whitelabel-fundament
-- (whitelabel-plan.md stap 1 en 2). Alleen aanvullend: de klant-app draait gewoon door.
--
-- Volgorde: eerst sql/schema.sql draaien (maakt de nieuwe tabellen aan, bestaande tabellen
-- blijven ongemoeid), daarna dit bestand één keer. Niet twee keer draaien: de ALTERs falen
-- dan op "Duplicate column" (onschadelijk, maar rommelig).

ALTER TABLE verhuizingen
    ADD COLUMN bedrijf_id INT UNSIGNED NULL AFTER naam,
    ADD COLUMN verhuisdatum DATE NULL AFTER bedrijf_id,
    ADD COLUMN adres_van VARCHAR(160) NULL AFTER verhuisdatum,
    ADD COLUMN adres_naar VARCHAR(160) NULL AFTER adres_van,
    ADD KEY idx_verhuizingen_bedrijf (bedrijf_id, verhuisdatum),
    ADD CONSTRAINT fk_verhuizingen_bedrijf FOREIGN KEY (bedrijf_id) REFERENCES bedrijven (id);

ALTER TABLE sessions
    ADD COLUMN meekijk_verhuizing_id INT UNSIGNED NULL AFTER active_verhuizing_id,
    ADD CONSTRAINT fk_sessions_meekijk FOREIGN KEY (meekijk_verhuizing_id) REFERENCES verhuizingen (id) ON DELETE SET NULL;

ALTER TABLE memberships
    MODIFY rol ENUM('admin', 'helper', 'sjouwer') NOT NULL DEFAULT 'helper';

-- Jezelf global-admin maken (pas het e-mailadres aan):
-- INSERT INTO platform_admins (user_id) SELECT id FROM users WHERE email = 'jij@example.nl';
