<?php

declare(strict_types=1);

namespace Padosoft\Rebel\EmailOtp\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Padosoft\Rebel\EmailOtp\Models\EmailOtpChallenge;
use Psr\Clock\ClockInterface;

/**
 * `php artisan rebel:email-otp:prune --days=7`
 *
 * Elimina le challenge OTP più vecchie di N giorni (retention/GDPR). Pensato per
 * lo scheduler. Le challenge sono comunque single-use e scadono da sole; questo
 * comando ripulisce lo storico.
 */
final class PruneChallengesCommand extends Command
{
    protected $signature = 'rebel:email-otp:prune {--days=7 : Elimina le challenge più vecchie di N giorni}';

    protected $description = 'Elimina le challenge OTP email più vecchie di N giorni.';

    public function handle(ClockInterface $clock): int
    {
        $daysOption = $this->option('days');
        $days = is_numeric($daysOption) ? max(1, (int) $daysOption) : 7;

        $threshold = CarbonImmutable::instance($clock->now())->subDays($days);

        $deleted = EmailOtpChallenge::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $count = is_int($deleted) ? $deleted : 0;

        $this->info("Eliminate {$count} challenge più vecchie di {$days} giorni.");

        return self::SUCCESS;
    }
}
