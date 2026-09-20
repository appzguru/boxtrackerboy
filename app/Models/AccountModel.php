<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountModel extends Model
{
    protected $table         = 'accounts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['naam', 'pincode'];

    public function findByPincode(string $pincode): ?array
    {
        return $this->where('pincode', $pincode)->first();
    }
}
