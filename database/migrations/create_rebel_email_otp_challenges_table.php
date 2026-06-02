<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebel_email_otp_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->string('tenant_id')->nullable();
            $table->string('guard')->nullable();
            $table->string('purpose');

            $table->string('identifier_type');
            // identifier e codice salvati SOLO come HMAC (mai in chiaro). 128 per algo > sha256.
            $table->string('identifier_hmac', 128);
            $table->unsignedTinyInteger('key_version');
            // salt random server-only per-challenge: con il pepper protegge il code_hmac.
            $table->string('code_salt', 64);
            // null quando la verifica è "provider-managed" (es. Twilio Verify).
            $table->string('code_hmac', 128)->nullable();

            $table->nullableMorphs('subject');

            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('channel')->default('email');

            $table->string('status')->default('pending');

            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resends')->default(0);

            $table->string('ip_hmac', 128)->nullable();
            $table->string('user_agent_hash', 128)->nullable();

            $table->string('idempotency_key')->nullable();

            $table->json('risk_context')->nullable();

            $table->timestamps();

            // Una sola challenge "attiva" per identifier+tenant+purpose si cerca spesso così:
            $table->index(['identifier_hmac', 'tenant_id', 'purpose', 'status']);
            $table->index(['idempotency_key']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rebel_email_otp_challenges');
    }
};
