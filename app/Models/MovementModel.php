<?php

namespace App\Models;

class MovementModel extends ScopedModel
{
    protected $table         = 'movements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['box_id', 'van_locatie', 'naar_locatie', 'door', 'op', 'batch_id'];

    public function journeyFor(int $boxId, int $limit = 20): array
    {
        return $this->where('box_id', $boxId)->orderBy('op', 'DESC')->limit($limit)->findAll();
    }

    /** Meest recent gebruikte locaties (naar_locatie), uniek, nieuwste eerst. */
    public function recentDestinations(int $limit = 8, array $exclude = []): array
    {
        $builder = $this->select('naar_locatie, MAX(op) as laatst')
            ->groupBy('naar_locatie')
            ->orderBy('laatst', 'DESC');

        if ($exclude) {
            $builder->whereNotIn('naar_locatie', $exclude);
        }

        return array_column($builder->limit($limit)->findAll(), 'naar_locatie');
    }
}
