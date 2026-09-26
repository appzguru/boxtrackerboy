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

    /** Limieten tegen misbruik (handoff.md §9). */
    public int $maxLabelsPerKeer          = 60;
    public int $maxDozenPerVerhuizing     = 1000;
    public int $maxVerhuizingenPerAccount = 10;

    public function stickerBase(): string
    {
        return rtrim($this->stickerBaseURL !== '' ? $this->stickerBaseURL : base_url(), '/');
    }
}
