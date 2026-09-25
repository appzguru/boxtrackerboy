<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['naam', 'email', 'password_hash', 'email_verified_at'];

    public const MIN_PASSWORD = 8;

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', self::normalizeEmail($email))->first();
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Controleert de invoer van een nieuw account; geeft een foutmelding of null. */
    public function validateNew(string $naam, string $email, string $password): ?string
    {
        if ($naam === '' || mb_strlen($naam) > 60) {
            return 'Vul je naam in.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return 'Dat e-mailadres klopt niet.';
        }
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            return 'Kies een wachtwoord van minstens ' . self::MIN_PASSWORD . ' tekens.';
        }
        if ($this->findByEmail($email)) {
            return 'Er is al een account met dit e-mailadres. Log in of vraag een nieuw wachtwoord aan.';
        }

        return null;
    }

    public function createUser(string $naam, string $email, string $password): int
    {
        return (int) $this->insert([
            'naam'          => $naam,
            'email'         => self::normalizeEmail($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ], true);
    }

    /**
     * Maakt een eenmalig token (verify/reset) en geeft het ruwe token terug; in de
     * database staat alleen de hash.
     */
    public function issueToken(int $userId, string $soort, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(24));
        db_connect()->table('user_tokens')->insert([
            'user_id'    => $userId,
            'soort'      => $soort,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlSeconds),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /** Verzilvert een token: geeft de user_id terug en markeert het gebruikt, of null. */
    public function consumeToken(string $token, string $soort): ?int
    {
        $db  = db_connect();
        $row = $db->table('user_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->where('soort', $soort)
            ->where('used_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }

        $db->table('user_tokens')->where('id', $row['id'])->update(['used_at' => date('Y-m-d H:i:s')]);

        return (int) $row['user_id'];
    }

    /** Stuurt (opnieuw) de mail om het e-mailadres te bevestigen. */
    public function sendVerification(array $user): void
    {
        $token = $this->issueToken((int) $user['id'], 'verify', 7 * 86400);
        (new \App\Libraries\Mailer())->send(
            $user['email'],
            'Bevestig je e-mailadres — Boxtracker',
            "Hoi {$user['naam']},\n\nBevestig je e-mailadres via deze link:\n\n"
            . site_url('verifieer/' . $token)
            . "\n\nDe link is 7 dagen geldig. Heb je geen account aangemaakt bij Boxtracker? Dan kun je deze mail negeren.\n"
        );
    }
}
