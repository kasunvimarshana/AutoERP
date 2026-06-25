<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\User\Constants\PlatformOperatorInvitationDeliveryStatus;
use Modules\User\Constants\PlatformOperatorInvitationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_operator_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('platform_operator_invitations_public_uk');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('token_hash', 64)->unique('platform_operator_invitations_token_uk');
            $table->text('delivery_token')->nullable();
            $table->enum('status', PlatformOperatorInvitationStatus::values())
                ->default(PlatformOperatorInvitationStatus::PENDING);
            $table->enum('delivery_status', PlatformOperatorInvitationDeliveryStatus::values())
                ->default(PlatformOperatorInvitationDeliveryStatus::QUEUED);
            $table->unsignedInteger('processing_attempt_count')->default(0);
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('mail_provider', 80)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->foreign('user_id', 'platform_operator_invitations_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'platform_operator_invitations_actor_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['user_id', 'status', 'expires_at'], 'platform_operator_invitations_user_status_idx');
            $table->index(['delivery_status', 'lease_expires_at'], 'platform_operator_invitations_delivery_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_operator_invitations');
    }
};
