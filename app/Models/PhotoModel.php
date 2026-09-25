<?php

namespace App\Models;

class PhotoModel extends ScopedModel
{
    public const MAX_PER_BOX = 3;

    protected $table         = 'photos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['box_id', 'bestandsnaam', 'op'];

    public function forBox(int $boxId): array
    {
        return $this->where('box_id', $boxId)->orderBy('op', 'ASC')->findAll();
    }

    /** Map van de foto's van één doos, per verhuizing gescheiden (handoff.md §9). */
    public static function dirFor(array $box): string
    {
        return WRITEPATH . 'uploads/' . (int) $box['verhuizing_id'] . '/' . (int) $box['id'];
    }
}
