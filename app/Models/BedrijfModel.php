<?php

namespace App\Models;

use App\Libraries\Tenant;
use CodeIgniter\Model;

/** Bedrijven (whitelabel). Alleen voor beheer; de ingang zelf loopt via App\Libraries\Tenant. */
class BedrijfModel extends Model
{
    protected $table         = 'bedrijven';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['naam', 'subdomein', 'status'];

    /** Alle bedrijven met aantallen medewerkers (actief) en verhuizingen. */
    public function overzicht(): array
    {
        return $this->select('bedrijven.*,
                (SELECT COUNT(*) FROM bedrijf_medewerkers m WHERE m.bedrijf_id = bedrijven.id AND m.actief = 1) AS medewerkers,
                (SELECT COUNT(*) FROM verhuizingen v WHERE v.bedrijf_id = bedrijven.id) AS verhuizingen')
            ->orderBy('bedrijven.naam', 'ASC')
            ->findAll();
    }

    /** Foutmelding of null. Het subdomein komt al genormaliseerd binnen (kleine letters, getrimd). */
    public function validateNew(string $naam, string $subdomein): ?string
    {
        if ($naam === '' || mb_strlen($naam) > 120) {
            return 'Geef het bedrijf een naam (max. 120 tekens).';
        }
        if (! Tenant::geldigSubdomein($subdomein)) {
            return 'Subdomein: alleen kleine letters, cijfers en streepjes (niet aan begin of eind), max. 40 tekens.';
        }
        if (Tenant::isGereserveerd($subdomein)) {
            return 'Dit subdomein is gereserveerd.';
        }
        if ($this->where('subdomein', $subdomein)->countAllResults() > 0) {
            return 'Dit subdomein is al in gebruik.';
        }

        return null;
    }

    public function medewerkers(int $bedrijfId): array
    {
        return db_connect()->table('bedrijf_medewerkers')
            ->select('bedrijf_medewerkers.*, users.naam, users.email')
            ->join('users', 'users.id = bedrijf_medewerkers.user_id')
            ->where('bedrijf_medewerkers.bedrijf_id', $bedrijfId)
            ->orderBy('bedrijf_medewerkers.actief', 'DESC')
            ->orderBy('users.naam', 'ASC')
            ->get()->getResultArray();
    }

    public function verhuizingen(int $bedrijfId): array
    {
        return db_connect()->table('verhuizingen')
            ->select('verhuizingen.id, verhuizingen.naam, verhuizingen.verhuisdatum, (SELECT COUNT(*) FROM boxes WHERE boxes.verhuizing_id = verhuizingen.id AND boxes.status != \'leeg\') AS dozen')
            ->where('verhuizingen.bedrijf_id', $bedrijfId)
            ->orderBy('verhuizingen.verhuisdatum IS NULL', 'ASC', false)
            ->orderBy('verhuizingen.verhuisdatum', 'DESC')
            ->orderBy('verhuizingen.naam', 'ASC')
            ->get()->getResultArray();
    }
}
