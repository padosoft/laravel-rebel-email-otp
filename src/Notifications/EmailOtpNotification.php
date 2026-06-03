<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email con il codice OTP. È queued (non blocca il flusso) e va sulla connessione/coda
 * dedicata "rebel". Il codice viaggia solo nel payload della coda (necessario per l'invio):
 * NON va mai loggato.
 *
 * @property string $code
 */
final class EmailOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $ttlSeconds,
        public string $purpose,
    ) {
        $this->onQueue('rebel');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) ceil($this->ttlSeconds / 60);

        return (new MailMessage)
            ->subject('Il tuo codice di accesso')
            ->line('Usa questo codice per accedere:')
            ->line('**'.$this->code.'**')
            ->line("Il codice scade tra {$minutes} minuti. Non condividerlo con nessuno.")
            ->line('Se non hai richiesto tu questo codice, ignora questa email.');
    }
}
