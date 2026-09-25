<?php

namespace App\Models;

class BoxModel extends ScopedModel
{
    protected $table            = 'boxes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'nummer', 'token', 'omschrijving', 'eigenaar', 'einddoel', 'huidige_locatie',
        'status', 'fragiel', 'eerst_openen', 'ingepakt_door', 'ingepakt_op',
    ];

    public function findByNummer(int $nummer): ?array
    {
        return $this->where('nummer', $nummer)->first();
    }

    /** Doos op token, alleen binnen de actieve verhuizing. */
    public function findByToken(string $token): ?array
    {
        return $this->where('token', $token)->first();
    }

    /**
     * Zoekt een sticker-token over álle verhuizingen heen (tokens zijn globaal uniek).
     * Bewust buiten de scope: alleen om te bepalen bij welke verhuizing een gescande
     * sticker hoort. Geeft alleen id, verhuizing_id en nummer terug — nooit inhoud.
     */
    public static function locateToken(string $token): ?array
    {
        return db_connect()->table('boxes')
            ->select('id, verhuizing_id, nummer')
            ->where('token', $token)
            ->get()->getRowArray() ?: null;
    }

    /** Nieuw globaal uniek token van 6 tekens (handoff.md §4). */
    public static function newToken(): string
    {
        helper('access');
        do {
            $token = random_code(6);
        } while (self::locateToken($token));

        return $token;
    }

    public function nextNummer(): int
    {
        $row = $this->selectMax('nummer')->first();

        return ((int) ($row['nummer'] ?? 0)) + 1;
    }

    /** Eerder gebruikte eigenaars, meest gebruikt eerst. */
    public function ownerSuggestions(int $limit = 8): array
    {
        $rows = $this->select('eigenaar, COUNT(*) as n')
            ->where('eigenaar IS NOT NULL')
            ->where('eigenaar !=', '')
            ->groupBy('eigenaar')
            ->orderBy('n', 'DESC')
            ->limit($limit)
            ->findAll();

        return array_column($rows, 'eigenaar');
    }

    /** Zoeken op inhoud, eigenaar en nummer. Met $onlyNummer (sjouwers) alleen op nummer. */
    public function search(string $q, bool $onlyNummer = false): array
    {
        if ($onlyNummer) {
            if (! ctype_digit($q)) {
                return [];
            }
            $this->where('nummer', (int) $q);
        } else {
            $this->groupStart()
                ->like('omschrijving', $q)
                ->orLike('eigenaar', $q);
            if (ctype_digit($q)) {
                $this->orWhere('nummer', (int) $q);
            }
            $this->groupEnd();
        }

        return $this->where('status !=', 'leeg')->orderBy('updated_at', 'DESC')->findAll(200);
    }

    public function counts(): array
    {
        $rows = $this->select('status, COUNT(*) as n')->groupBy('status')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }

        return $out;
    }
}
