<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * App-specifieke instellingen. Overschrijfbaar via .env, bv.
 * `boxtracker.stickerBaseURL = 'https://boxtracker.nl'`.
 */
class Boxtracker extends BaseConfig
{
    /**
     * Basis-URL in de QR-code op stickers (handoff.md §4). Bewust het hoofddomein, niet
     * app.: boxtracker.nl/d/* stuurt door naar de app. Leeg = app.baseURL (lokaal).
     */
    public string $stickerBaseURL = '';

    /**
     * Alleen voor de testomgeving (minisaas): een gescande sticker die hier niet bestaat
     * gaat door naar deze URL + /d/…, zodat oude v1-stickers bij prd uitkomen. Leeg = uit.
     */
    public string $stickerFallbackURL = '';

    /** 'dev' = testomgeving: waarschuwingsbalk, andere kleuren, "DEV" in titel en op stickers. Leeg = prd. */
    public string $omgeving = '';

    public function isDev(): bool
    {
        return $this->omgeving === 'dev';
    }

    /**
     * Whitelabel: `<subdomein>.<tenantDomein>` is de ingang van een bedrijf (App\Libraries\Tenant).
     * Lokaal testen kan met `boxtracker.tenantDomein = localtest.me` en dan
     * http://<subdomein>.localtest.me:8080 (wijst altijd naar je eigen machine).
     */
    public string $tenantDomein = 'boxtracker.nl';

    /** Subdomeinen die nooit een bedrijf zijn. */
    public array $gereserveerdeSubdomeinen = [
        'app', 'www', 'mail', 'ftp', 'pop', 'smtp', 'portfolio', 'beheer', 'api', 'dev',
    ];

    /**
     * Alleen op de testomgeving (omgeving = dev): doe alsof elk verzoek op het subdomein van
     * dit bedrijf binnenkomt. Zo is de bedrijfsversie te testen zonder eigen hostnaam.
     */
    public string $devBedrijf = '';

    /** Limieten tegen misbruik (handoff.md §9). */
    public int $maxLabelsPerKeer          = 60;
    public int $maxDozenPerVerhuizing     = 1000;
    public int $maxVerhuizingenPerAccount = 10;

    public function stickerBase(): string
    {
        return rtrim($this->stickerBaseURL !== '' ? $this->stickerBaseURL : base_url(), '/');
    }
}
