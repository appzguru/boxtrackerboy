<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountSessionModel extends Model
{
    protected $table         = 'account_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['account_id', 'token'];

    public function createFor(int $accountId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->insert(['account_id' => $accountId, 'token' => $token]);

        return $token;
    }

    public function deleteByToken(string $token): void
    {
        $this->where('token', $token)->delete();
    }
}
