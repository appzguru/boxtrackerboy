<?php

namespace App\Models;

class OpnameFotoModel extends ScopedModel
{
    public const MAX_PER_ITEM = 5;

    protected $table         = 'opname_fotos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['item_id', 'bestandsnaam', 'op'];

    public function forItem(int $itemId): array
    {
        return $this->where('item_id', $itemId)->orderBy('op', 'ASC')->findAll();
    }

    /** Alle foto's van de actieve verhuizing, per item-id. */
    public function perItem(): array
    {
        $out = [];
        foreach ($this->orderBy('op', 'ASC')->findAll() as $f) {
            $out[(int) $f['item_id']][] = $f;
        }

        return $out;
    }

    /** Map van de foto's van één opname-item, per verhuizing gescheiden. */
    public static function dirFor(array $item): string
    {
        return WRITEPATH . 'uploads/' . (int) $item['verhuizing_id'] . '/opname/' . (int) $item['id'];
    }
}
