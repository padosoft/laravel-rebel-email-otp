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
 * Deletes OTP challenges older than N days (retention/GDPR). Designed for the
 * scheduler. Challenges are single-use and expire on their own anyway; this
 * command cleans up the history.
 */
final class PruneChallengesCommand extends Command
{
    protected $signature = 'rebel:email-otp:prune {--days=7 : Delete challenges older than N days}';

    protected $description = 'Delete email OTP challenges older than N days.';

    public function handle(ClockInterface $clock): int
    {
        $daysOption = $this->option('days');
        $days = is_numeric($daysOption) ? max(1, (int) $daysOption) : 7;

        $threshold = CarbonImmutable::instance($clock->now())->subDays($days);

        $deleted = EmailOtpChallenge::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $count = is_int($deleted) ? $deleted : 0;

        $this->info("Deleted {$count} challenges older than {$days} days.");

        return self::SUCCESS;
    }
}
