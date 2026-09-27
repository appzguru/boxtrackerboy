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
    protected $allowedFields = ['naam', 'subdomein', 'status', 'blok_memo', 'blok_sinds'];

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

    /**
     * Planningsoverzicht: alle verhuizingen van het bedrijf met wat er vóór de verhuisdag toe
     * doet. Komende verhuizingen eerst (op datum), dan zonder datum, dan voorbije.
     */
    public function planning(int $bedrijfId): array
    {
        $vandaag = date('Y-m-d');

        return db_connect()->table('verhuizingen v')
            ->select("v.id, v.naam, v.verhuisdatum, v.adres_van, v.adres_naar,
                (SELECT COUNT(*) FROM boxes b WHERE b.verhuizing_id = v.id) AS stickers,
                (SELECT COUNT(*) FROM boxes b WHERE b.verhuizing_id = v.id AND b.status != 'leeg') AS ingepakt,
                (SELECT COUNT(*) FROM boxes b WHERE b.verhuizing_id = v.id AND b.status != 'leeg' AND b.fragiel = 1) AS fragiel,
                (SELECT GROUP_CONCAT(u.naam ORDER BY u.naam SEPARATOR ', ') FROM memberships m
                    JOIN users u ON u.id = m.user_id
                    JOIN bedrijf_medewerkers bm ON bm.user_id = m.user_id AND bm.bedrijf_id = v.bedrijf_id
                    WHERE m.verhuizing_id = v.id) AS ploeg", false)
            ->where('v.bedrijf_id', $bedrijfId)
            ->orderBy("CASE WHEN v.verhuisdatum >= '" . $vandaag . "' THEN 0 WHEN v.verhuisdatum IS NULL THEN 1 ELSE 2 END", 'ASC', false)
            ->orderBy("CASE WHEN v.verhuisdatum >= '" . $vandaag . "' THEN v.verhuisdatum END", 'ASC', false)
            ->orderBy('v.verhuisdatum', 'DESC')
            ->orderBy('v.naam', 'ASC')
            ->get()->getResultArray();
    }

    /** Verhuizing van dít bedrijf, of null. */
    public function verhuizing(int $bedrijfId, int $verhuizingId): ?array
    {
        return db_connect()->table('verhuizingen')
            ->where('id', $verhuizingId)
            ->where('bedrijf_id', $bedrijfId)
            ->get()->getRowArray() ?: null;
    }

    /** Leden van een verhuizing, met hun medewerkersrol bij dit bedrijf (null = bewoner). */
    public function leden(int $bedrijfId, int $verhuizingId): array
    {
        return db_connect()->table('memberships m')
            ->select('m.id, m.rol, m.user_id, u.naam, u.email, bm.rol AS medewerker_rol')
            ->join('users u', 'u.id = m.user_id')
            ->join('bedrijf_medewerkers bm', 'bm.user_id = m.user_id AND bm.bedrijf_id = ' . (int) $bedrijfId, 'left')
            ->where('m.verhuizing_id', $verhuizingId)
            ->orderBy('u.naam', 'ASC')
            ->get()->getResultArray();
    }

    /** Actieve inpakkers en sjouwers: die wijst de planner per verhuizing toe. */
    public function ploegKandidaten(int $bedrijfId): array
    {
        return db_connect()->table('bedrijf_medewerkers bm')
            ->select('bm.user_id, bm.rol, u.naam')
            ->join('users u', 'u.id = bm.user_id')
            ->where('bm.bedrijf_id', $bedrijfId)
            ->where('bm.actief', 1)
            ->whereIn('bm.rol', ['inpakker', 'sjouwer'])
            ->orderBy('bm.rol', 'ASC')
            ->orderBy('u.naam', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Zet de ploeg van een verhuizing: aangevinkte inpakkers (helper) en sjouwers (sjouwer)
     * worden lid, niet-aangevinkte medewerkers eruit. Bewoners blijven altijd ongemoeid.
     */
    public function zetPloeg(int $bedrijfId, int $verhuizingId, array $userIds): void
    {
        $db         = db_connect();
        $kandidaten = array_column($this->ploegKandidaten($bedrijfId), 'rol', 'user_id');
        $gekozen    = array_intersect_key($kandidaten, array_flip(array_map('intval', $userIds)));

        $db->transStart();
        $huidig = $this->leden($bedrijfId, $verhuizingId);
        foreach ($huidig as $l) {
            if ($l['medewerker_rol'] !== null && ! isset($gekozen[$l['user_id']])) {
                $db->table('memberships')->where('id', $l['id'])->delete();
                $db->table('sessions')->where('user_id', $l['user_id'])->where('active_verhuizing_id', $verhuizingId)->update(['active_verhuizing_id' => null]);
            }
        }
        $alLid = array_column($huidig, 'id', 'user_id');
        foreach ($gekozen as $userId => $rol) {
            $memberRol = $rol === 'sjouwer' ? 'sjouwer' : 'helper';
            if (isset($alLid[$userId])) {
                $db->table('memberships')->where('id', $alLid[$userId])->update(['rol' => $memberRol]);
            } else {
                $db->table('memberships')->insert(['verhuizing_id' => $verhuizingId, 'user_id' => $userId, 'rol' => $memberRol, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }
        $db->transComplete();
    }
}
