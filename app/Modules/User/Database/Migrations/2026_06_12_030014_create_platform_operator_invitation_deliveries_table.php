<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const QUEUED_STATUS = 'queued';

    public function up(): void
    {
        Schema::create('platform_operator_invitation_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invitation_id')->constrained('platform_operator_invitations', 'id')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 30)->default(self::QUEUED_STATUS);
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('mail_provider', 80)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->unique(['invitation_id', 'attempt_number'], 'platform_operator_invitation_delivery_attempt_uk');
            $table->index(['status', 'lease_expires_at'], 'platform_operator_invitation_delivery_claim_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_invitation_deliveries');
    }
};
