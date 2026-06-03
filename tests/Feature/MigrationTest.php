<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the rebel_email_otp_challenges table with the key columns', function (): void {
    expect(Schema::hasTable('rebel_email_otp_challenges'))->toBeTrue()
        ->and(Schema::hasColumns('rebel_email_otp_challenges', [
            'id', 'tenant_id', 'guard', 'purpose', 'identifier_type', 'identifier_hmac',
            'key_version', 'code_salt', 'code_hmac', 'status', 'expires_at', 'consumed_at',
            'attempts', 'resends', 'idempotency_key', 'risk_context',
        ]))->toBeTrue();
});
