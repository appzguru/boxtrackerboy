<?php

namespace App\Models;

use CodeIgniter\Model;

class PhotoModel extends Model
{
    protected $table         = 'photos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['box_id', 'bestandsnaam', 'op'];

    public function forBox(int $boxId): array
    {
        return $this->where('box_id', $boxId)->orderBy('op', 'ASC')->findAll();
    }
}
