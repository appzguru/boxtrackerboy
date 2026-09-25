<?php

namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table         = 'locations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['naam', 'soort', 'actief'];

    /** Zorgt dat een naam bestaat als locatie (voor suggesties later); negeert duplicaten. */
    public function remember(string $naam, string $soort = 'overig'): void
    {
        $naam = trim($naam);
        if ($naam === '') {
            return;
        }
        $exists = $this->where('naam', $naam)->first();
        if (! $exists) {
            $this->insert(['naam' => $naam, 'soort' => $soort, 'actief' => 1]);
        }
    }

    public function suggestions(string $soort = null, int $limit = 12): array
    {
        $builder = $this->where('actief', 1);
        if ($soort) {
            $builder->where('soort', $soort);
        }

        return array_column($builder->orderBy('naam', 'ASC')->limit($limit)->findAll(), 'naam');
    }

    /** Verbergt een naam als suggestie (bv. een typo), zonder de verplaatshistorie aan te passen. */
    public function hide(string $naam): void
    {
        $naam = trim($naam);
        if ($naam === '') {
            return;
        }
        $this->where('naam', $naam)->set(['actief' => 0])->update();
    }

    /** Namen die verborgen zijn (actief = 0), voor het uitfilteren van suggesties elders. */
    public function hiddenNames(): array
    {
        return array_column($this->where('actief', 0)->findAll(), 'naam');
    }
}
