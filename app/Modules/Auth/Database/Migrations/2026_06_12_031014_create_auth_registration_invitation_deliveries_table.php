<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Constants\InvitationDeliveryStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_registration_invitation_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('auth_reg_invite_deliveries_public_uk');
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('invitation_id');
            $table->unsignedInteger('attempt_number');
            $table->enum('status', InvitationDeliveryStatus::values())
                ->default(InvitationDeliveryStatus::QUEUED);
            $table->unsignedInteger('processing_attempt_count')->default(0);
            $table->uuid('claim_token')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('provider', 100)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_reg_invite_deliveries_id_tenant_uk');
            $table->unique(
                ['tenant_id', 'invitation_id', 'attempt_number'],
                'auth_reg_invite_deliveries_attempt_uk',
            );
            $table->foreign(
                ['invitation_id', 'tenant_id'],
                'auth_reg_invite_deliveries_invitation_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('auth_registration_invitations')
                ->restrictOnDelete();
            $table->index(
                ['status', 'lease_expires_at', 'requested_at'],
                'auth_reg_invite_deliveries_work_idx',
            );
            $table->index(
                ['tenant_id', 'invitation_id', 'requested_at'],
                'auth_reg_invite_deliveries_invitation_idx',
            );
            $table->index(
                ['provider', 'provider_message_id'],
                'auth_reg_invite_deliveries_provider_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_registration_invitation_deliveries');
    }
};
