<?php

namespace App\Models;

use CodeIgniter\Model;

class BoxModel extends Model
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

    public function search(string $q): array
    {
        $builder = $this->groupStart()
            ->like('omschrijving', $q)
            ->orLike('eigenaar', $q);

        if (ctype_digit($q)) {
            $builder->orWhere('nummer', (int) $q);
        }
        $builder->groupEnd();

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
