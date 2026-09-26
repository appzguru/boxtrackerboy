<?php

namespace App\Libraries;

/**
 * Verstuurt de paar systeemmails (bevestigen, wachtwoord reset — handoff.md §10).
 * Zonder SMTP-host (lokaal) wordt de platte-tekstversie naar writable/mail/ geschreven
 * in plaats van verstuurd, zodat je de links toch kunt volgen.
 */
class Mailer
{
    /** Mail met een knop-link (bevestigen, wachtwoord reset). */
    public function sendAction(string $to, string $naam, string $subject, string $intro, string $url, string $buttonLabel, string $footer): bool
    {
        $html = view('emails/action', compact('subject', 'naam', 'intro', 'url', 'buttonLabel', 'footer'));
        $text = "Hoi {$naam},\n\n{$intro}\n\n{$url}\n\n{$footer}\n";

        return $this->dispatch($to, $subject, $html, $text);
    }

    private function dispatch(string $to, string $subject, string $html, string $text): bool
    {
        $config = config('Email');

        if ($config->SMTPHost === '') {
            $dir = WRITEPATH . 'mail';
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents(
                $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt',
                "To: {$to}\nSubject: {$subject}\n\n{$text}\n"
            );

            return true;
        }

        $email = service('email');
        $email->setFrom($config->fromEmail ?: 'noreply@boxtracker.nl', $config->fromName ?: 'Boxtracker');
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMailType('html');
        $email->setMessage($html);
        $email->setAltMessage($text);

        if (! $email->send(false)) {
            log_message('error', 'Mail naar {to} mislukt: {debug}', ['to' => $to, 'debug' => $email->printDebugger(['headers'])]);

            return false;
        }

        return true;
    }
}
