<?php

namespace App\Controllers;

use App\Libraries\Access;
use App\Libraries\Mailer;
use App\Models\InviteModel;
use App\Models\UserModel;
use App\Models\VerhuizingModel;

/** Registreren, inloggen, uitloggen, wachtwoord vergeten, e-mail bevestigen (handoff.md §3.1). */
class Auth extends BaseController
{
    /** Alleen lokale paden als redirect-doel (geen open redirect). */
    private function safeNext(?string $next): string
    {
        $next = (string) $next;

        return (str_starts_with($next, '/') && ! str_starts_with($next, '//') && ! str_contains($next, '\\')) ? $next : '/';
    }

    /** Max $max pogingen per kwartier per sleutel. true = mag. */
    private function throttle(string $key, int $max = 10): bool
    {
        return service('throttler')->check(md5($key), $max, 15 * MINUTE);
    }

    public function registerForm()
    {
        if (access()->user()) {
            return redirect()->to('/');
        }

        $invite = $this->inviteFromRequest();

        return $this->view('auth_register', [
            'title'  => 'Account aanmaken — Boxtracker',
            'invite' => $invite,
            'old'    => [],
        ]);
    }

    private function inviteFromRequest(): ?array
    {
        $token = (string) ($this->request->getGetPost('uitnodiging') ?? '');

        return $token !== '' ? (new InviteModel())->findUsable($token) : null;
    }

    public function register()
    {
        $invite   = $this->inviteFromRequest();
        $naam     = trim((string) $this->request->getPost('naam'));
        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $vhNaam   = trim((string) $this->request->getPost('verhuizing'));

        $users = new UserModel();
        $fout  = $this->throttle('register' . $this->request->getIPAddress(), 5) ? $users->validateNew($naam, $email, $password) : 'Te veel pogingen. Probeer het over een kwartier opnieuw.';
        if (! $fout && ! $invite && mb_strlen($vhNaam) > 80) {
            $fout = 'De naam van de verhuizing is te lang.';
        }

        if ($fout) {
            return $this->view('auth_register', [
                'title'  => 'Account aanmaken — Boxtracker',
                'invite' => $invite,
                'fout'   => $fout,
                'old'    => ['naam' => $naam, 'email' => $email, 'verhuizing' => $vhNaam],
            ]);
        }

        $userId = $users->createUser($naam, $email, $password);
        $users->sendVerification($users->find($userId));

        // Via een uitnodiging: direct lid, géén eigen verhuizing. Anders: eigen verhuizing als admin.
        if ($invite) {
            (new InviteModel())->accept($invite, $userId);
            $active = (int) $invite['verhuizing_id'];
        } else {
            $active = (new VerhuizingModel())->createFor($userId, $vhNaam !== '' ? $vhNaam : 'Verhuizing van ' . $naam);
        }

        $response = redirect()->to('/');
        login_user($userId, $response, $active);

        return $response;
    }

    public function loginForm()
    {
        $next = $this->safeNext($this->request->getGet('next'));
        if (access()->user()) {
            return redirect()->to($next);
        }

        return $this->view('auth_login', [
            'title' => 'Inloggen — Boxtracker',
            'next'  => $next,
            'email' => '',
        ]);
    }

    public function login()
    {
        $email    = UserModel::normalizeEmail((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $next     = $this->safeNext($this->request->getPost('next'));

        $ok = $this->throttle('login-ip' . $this->request->getIPAddress(), 30)
            && $this->throttle('login-email' . $email, 10);

        $user = $ok ? (new UserModel())->findByEmail($email) : null;
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return $this->view('auth_login', [
                'title' => 'Inloggen — Boxtracker',
                'next'  => $next,
                'email' => $email,
                'fout'  => $ok ? 'E-mailadres of wachtwoord klopt niet.' : 'Te veel pogingen. Probeer het over een kwartier opnieuw.',
            ]);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            (new UserModel())->update($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $response = redirect()->to($next);
        login_user((int) $user['id'], $response);

        return $response;
    }

    public function logout()
    {
        $db = db_connect();
        if ($token = $this->request->getCookie(Access::USER_COOKIE)) {
            $db->table('sessions')->where('token', $token)->delete();
        }
        if ($token = $this->request->getCookie(Access::GUEST_COOKIE)) {
            $db->table('guest_sessions')->where('token', $token)->update(['revoked_at' => date('Y-m-d H:i:s')]);
        }

        return redirect()->to('/login')
            ->deleteCookie(Access::USER_COOKIE)
            ->deleteCookie(Access::GUEST_COOKIE);
    }

    public function forgotForm()
    {
        return $this->view('auth_forgot', ['title' => 'Wachtwoord vergeten — Boxtracker']);
    }

    public function forgot()
    {
        $email = UserModel::normalizeEmail((string) $this->request->getPost('email'));

        if ($this->throttle('forgot' . $this->request->getIPAddress(), 5) && $this->throttle('forgot' . $email, 3)) {
            $users = new UserModel();
            if ($user = $users->findByEmail($email)) {
                $token = $users->issueToken((int) $user['id'], 'reset', 3600);
                (new Mailer())->send(
                    $user['email'],
                    'Nieuw wachtwoord — Boxtracker',
                    "Hoi {$user['naam']},\n\nKies een nieuw wachtwoord via deze link:\n\n"
                    . site_url('wachtwoord/' . $token)
                    . "\n\nDe link is een uur geldig. Heb je dit niet zelf aangevraagd? Dan kun je deze mail negeren.\n"
                );
            }
        }

        // Altijd dezelfde melding: niet verklappen of een adres bestaat.
        return $this->view('auth_message', [
            'title'   => 'Check je mail — Boxtracker',
            'kop'     => 'Check je mail',
            'tekst'   => 'Als er een account bij dit adres hoort, staat er nu een mail klaar met een link om een nieuw wachtwoord te kiezen. De link is een uur geldig.',
            'knop'    => ['url' => '/login', 'label' => 'Terug naar inloggen'],
        ]);
    }

    public function resetForm(string $token)
    {
        return $this->view('auth_reset', ['title' => 'Nieuw wachtwoord — Boxtracker', 'token' => $token]);
    }

    public function reset(string $token)
    {
        $password = (string) $this->request->getPost('password');
        if (mb_strlen($password) < UserModel::MIN_PASSWORD) {
            return $this->view('auth_reset', [
                'title' => 'Nieuw wachtwoord — Boxtracker',
                'token' => $token,
                'fout'  => 'Kies een wachtwoord van minstens ' . UserModel::MIN_PASSWORD . ' tekens.',
            ]);
        }

        $users  = new UserModel();
        $userId = $users->consumeToken($token, 'reset');
        if (! $userId) {
            return $this->view('auth_message', [
                'title' => 'Link verlopen — Boxtracker',
                'kop'   => 'Deze link werkt niet meer',
                'tekst' => 'De link is al gebruikt of verlopen. Vraag een nieuwe aan.',
                'knop'  => ['url' => '/wachtwoord-vergeten', 'label' => 'Nieuwe link aanvragen'],
            ]);
        }

        // Nieuw wachtwoord: alle bestaande sessies eruit (bv. een kwijtgeraakt toestel).
        // De reset-link bewijst ook dat het e-mailadres klopt.
        $user = $users->find($userId);
        $users->update($userId, [
            'password_hash'     => password_hash($password, PASSWORD_DEFAULT),
            'email_verified_at' => $user['email_verified_at'] ?? date('Y-m-d H:i:s'),
        ]);
        db_connect()->table('sessions')->where('user_id', $userId)->delete();

        $response = redirect()->to('/');
        login_user($userId, $response);

        return $response;
    }

    public function verify(string $token)
    {
        $users  = new UserModel();
        $userId = $users->consumeToken($token, 'verify');
        if ($userId) {
            $users->update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
        }

        return $this->view('auth_message', [
            'title' => 'E-mail bevestigen — Boxtracker',
            'kop'   => $userId ? 'Je e-mailadres is bevestigd' : 'Deze link werkt niet meer',
            'tekst' => $userId ? 'Bedankt! Je kunt nu ook anderen uitnodigen voor je verhuizing.' : 'De link is al gebruikt of verlopen. Log in en vraag een nieuwe bevestigingsmail aan.',
            'knop'  => ['url' => '/', 'label' => 'Naar Boxtracker'],
        ]);
    }

    /** Nieuwe bevestigingsmail (vanuit de balk "bevestig je e-mail"). */
    public function resendVerification()
    {
        $user = access()->user();
        if ($user && ! $user['email_verified_at'] && $this->throttle('verify' . $user['id'], 3)) {
            (new UserModel())->sendVerification($user);
        }

        return redirect()->back()->with('message', 'We hebben een nieuwe bevestigingsmail gestuurd naar ' . ($user['email'] ?? '') . '.');
    }
}
