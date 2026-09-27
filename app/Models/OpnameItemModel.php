<?php

namespace App\Models;

/**
 * Foto-opname vooraf (whitelabel-plan.md stap 5): vaste punten en losse items, gescoped op de
 * actieve verhuizing zoals dozen.
 */
class OpnameItemModel extends ScopedModel
{
    /** Vaste punten, in de volgorde waarin je ze langsloopt. */
    public const PUNTEN = [
        'straat'      => ['Straat', 'De straat voor de deur: kan de wagen erbij, is er plek om te parkeren?'],
        'voordeur'    => ['Voordeur', 'Voordeur en entree: hoe breed, zijn er drempels of treden?'],
        'trappenhuis' => ['Trappenhuis', 'Trap of trappenhuis: breedte, bochten, is er een lift?'],
        'raam'        => ['Raam', 'Het grootste raam aan de straatkant: kan er een verhuislift voor?'],
    ];

    protected $table         = 'opname_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['soort', 'punt', 'omschrijving', 'kamer', 'risico', 'risico_notitie', 'created_by', 'created_at'];

    /** Alles van de actieve verhuizing, met aantal foto's: punten in vaste volgorde, dan items. */
    public function overzicht(): array
    {
        $rows = $this->select('opname_items.*, (SELECT COUNT(*) FROM opname_fotos f WHERE f.item_id = opname_items.id) AS fotos')
            ->orderBy('opname_items.created_at', 'ASC')
            ->findAll();

        $punten = [];
        $items  = [];
        foreach ($rows as $r) {
            if ($r['soort'] === 'punt') {
                $punten[$r['punt']] = $r;
            } else {
                $items[] = $r;
            }
        }

        return ['punten' => $punten, 'items' => $items];
    }

    /** Het item van een vast punt; wordt aangemaakt bij de eerste foto. */
    public function voorPunt(string $punt, string $door): array
    {
        $row = $this->where('soort', 'punt')->where('punt', $punt)->first();
        if ($row) {
            return $row;
        }

        $id = $this->insert(['soort' => 'punt', 'punt' => $punt, 'created_by' => $door, 'created_at' => date('Y-m-d H:i:s')], true);

        return $this->find($id);
    }
}
