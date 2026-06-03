<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email carrying the OTP code. It is queued (it does not block the flow) and goes onto the dedicated "rebel" queue.
 *
 * It implements ShouldBeEncrypted: the job payload (which contains the plaintext code,
 * required for delivery) is ENCRYPTED in the queue, so a dump of Redis/`failed_jobs`
 * does not expose the active OTP. The code must never be logged anyway.
 *
 * @property string $code
 */
final class EmailOtpNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
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
