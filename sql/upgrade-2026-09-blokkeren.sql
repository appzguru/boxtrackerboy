-- Eenmalige upgrade: softblock/hardblock met memo voor bedrijven (na upgrade-2026-09-bedrijven.sql).
-- Kan vóór de code live: de huidige code kijkt alleen of status 'actief' is.
-- Een bestaand 'geblokkeerd' bedrijf wordt 'hardblock'.

ALTER TABLE bedrijven
    MODIFY status ENUM('actief', 'geblokkeerd', 'softblock', 'hardblock') NOT NULL DEFAULT 'actief',
    ADD COLUMN blok_memo VARCHAR(500) NULL AFTER status,
    ADD COLUMN blok_sinds DATETIME NULL AFTER blok_memo;

UPDATE bedrijven SET status = 'hardblock', blok_sinds = NOW() WHERE status = 'geblokkeerd';

ALTER TABLE bedrijven
    MODIFY status ENUM('actief', 'softblock', 'hardblock') NOT NULL DEFAULT 'actief';
