<?php

namespace App\Libraries;

/**
 * Verstuurt de paar systeemmails (bevestigen, wachtwoord reset — handoff.md §10).
 * Zonder SMTP-host (lokaal) wordt de mail naar writable/mail/ geschreven in plaats
 * van verstuurd, zodat je de links toch kunt volgen.
 */
class Mailer
{
    public function send(string $to, string $subject, string $body): bool
    {
        $config = config('Email');

        if ($config->SMTPHost === '') {
            $dir = WRITEPATH . 'mail';
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents(
                $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt',
                "To: {$to}\nSubject: {$subject}\n\n{$body}\n"
            );

            return true;
        }

        $email = service('email');
        $email->setFrom($config->fromEmail ?: 'noreply@boxtracker.nl', $config->fromName ?: 'Boxtracker');
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($body);

        if (! $email->send(false)) {
            log_message('error', 'Mail naar {to} mislukt: {debug}', ['to' => $to, 'debug' => $email->printDebugger(['headers'])]);

            return false;
        }

        return true;
    }
}
