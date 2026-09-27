<?php

namespace App\Libraries;

use Config\Boxtracker;

/**
 * Via welke ingang komt dit verzoek binnen (whitelabel-plan.md §3)?
 *
 * - klant       — app.boxtracker.nl, boxtracker.nl, localhost, gereserveerde subdomeinen:
 *                 de klant-app, alleen particuliere verhuizingen (bedrijf_id NULL);
 * - bedrijf     — <sub>.boxtracker.nl van een bedrijf: alleen diens verhuizingen;
 * - onbekend    — subdomein zonder bedrijf: 404.
 *
 * Een bedrijf kan geblokkeerd zijn (blok()): softblock = geen nieuwe verhuizingen,
 * hardblock = medewerkers kunnen nergens meer bij. Klanten (bewoners) merken van
 * geen van beide iets — zie Access.
 *
 * Eén plek voor de vraag "welk bedrijf?" — nergens anders in de code naar hostnamen kijken.
 */
class Tenant
{
    public const KLANT    = 'klant';
    public const BEDRIJF  = 'bedrijf';
    public const ONBEKEND = 'onbekend';

    public const ACTIEF    = 'actief';
    public const SOFTBLOCK = 'softblock';
    public const HARDBLOCK = 'hardblock';

    private string $status   = self::KLANT;
    private ?array $bedrijf  = null;

    public function __construct(string $host, ?Boxtracker $config = null)
    {
        $config ??= config(Boxtracker::class);
        $sub = self::subdomeinVan($host, $config);
        if ($sub === null) {
            return;
        }

        $this->status = self::ONBEKEND;
        if (! self::geldigSubdomein($sub)) {
            return;
        }

        $row = db_connect()->table('bedrijven')->where('subdomein', $sub)->get()->getRowArray();
        if ($row) {
            $this->bedrijf = ['id' => (int) $row['id'], 'naam' => $row['naam'], 'subdomein' => $row['subdomein'], 'blok' => $row['status']];
            $this->status  = self::BEDRIJF;
        }
    }

    /** Subdomein uit de hostnaam, of null voor de klant-app (hoofddomein, gereserveerd, ander domein). */
    public static function subdomeinVan(string $host, Boxtracker $config): ?string
    {
        if ($config->isDev() && $config->devBedrijf !== '') {
            return strtolower($config->devBedrijf);
        }

        [$host] = explode(':', strtolower(trim($host)), 2);
        $suffix = '.' . strtolower($config->tenantDomein);
        if ($config->tenantDomein === '' || ! str_ends_with($host, $suffix)) {
            return null;
        }

        $sub = substr($host, 0, -strlen($suffix));

        return in_array($sub, $config->gereserveerdeSubdomeinen, true) ? null : $sub;
    }

    /** Eén DNS-label: kleine letters, cijfers en streepjes, niet aan het begin of eind. */
    public static function geldigSubdomein(string $sub): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])?$/', $sub);
    }

    public static function isGereserveerd(string $sub): bool
    {
        return in_array($sub, config(Boxtracker::class)->gereserveerdeSubdomeinen, true);
    }

    /** Volledige URL op het subdomein van een bedrijf, met schema en poort van app.baseURL. */
    public static function urlVoor(string $subdomein, string $path = ''): string
    {
        $base   = parse_url(config('App')->baseURL);
        $scheme = $base['scheme'] ?? 'https';
        $port   = isset($base['port']) ? ':' . $base['port'] : '';

        return $scheme . '://' . $subdomein . '.' . config(Boxtracker::class)->tenantDomein . $port . '/' . ltrim($path, '/');
    }

    public function status(): string
    {
        return $this->status;
    }

    /** Bedrijfssubdomein (bestaand bedrijf, ook als het geblokkeerd is). */
    public function isBedrijf(): bool
    {
        return $this->status === self::BEDRIJF;
    }

    public function isKlant(): bool
    {
        return $this->status === self::KLANT;
    }

    public function bedrijf(): ?array
    {
        return $this->bedrijf;
    }

    /** actief, softblock of hardblock; null in de klant-app. */
    public function blok(): ?string
    {
        return $this->bedrijf['blok'] ?? null;
    }

    public function isHardblock(): bool
    {
        return $this->blok() === self::HARDBLOCK;
    }

    /** Softblock (en hardblock): alsof de credits op zijn — geen nieuwe verhuizingen. */
    public function magNieuweVerhuizingen(): bool
    {
        return $this->isKlant() || $this->blok() === self::ACTIEF;
    }

    /** Id van het bedrijf bij deze hostnaam, anders null. */
    public function bedrijfId(): ?int
    {
        return $this->isBedrijf() ? $this->bedrijf['id'] : null;
    }

    /**
     * Beperkt een query (builder of model) tot verhuizingen van deze ingang, via de kolom
     * met de bedrijf_id van de verhuizing. Zelfde regel als owns().
     *
     * @template T of \CodeIgniter\Database\BaseBuilder|\CodeIgniter\Model
     *
     * @param T $query
     *
     * @return T
     */
    public function scope($query, string $kolom = 'verhuizingen.bedrijf_id')
    {
        return match ($this->status) {
            self::KLANT   => $query->where($kolom . ' IS NULL'),
            self::BEDRIJF => $query->where($kolom, $this->bedrijf['id']),
            default       => $query->where('1 = 0'),
        };
    }

    /**
     * Hoort een verhuizing (met deze bedrijf_id) bij deze ingang? Klant-app: alleen NULL.
     * Bedrijf: alleen dat bedrijf (ook geblokkeerd — de blokkade zit in Access). Onbekend: nooit.
     */
    public function owns(?int $verhuizingBedrijfId): bool
    {
        return match ($this->status) {
            self::KLANT   => $verhuizingBedrijfId === null,
            self::BEDRIJF => $verhuizingBedrijfId === $this->bedrijf['id'],
            default       => false,
        };
    }
}
