<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_onboarding_states', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->primary()->constrained('tenants', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->enum('status', [
                'pending',
                'provisioning',
                'awaiting_domain',
                'ready',
                'completed',
                'failed',
            ])->default('pending');
            $table->string('initial_admin_email')->nullable();
            $table->unsignedBigInteger('root_organization_unit_id')->nullable();
            $table->unsignedBigInteger('super_admin_role_id')->nullable();
            $table->unsignedBigInteger('invitation_id')->nullable();
            $table->json('completed_steps')->nullable();
            $table->string('last_error', 1000)->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign(
                ['root_organization_unit_id', 'tenant_id'],
                'tenant_onboarding_root_org_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(
                ['super_admin_role_id', 'tenant_id'],
                'tenant_onboarding_admin_role_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('roles')
                ->restrictOnDelete();
            $table->foreign(
                ['invitation_id', 'tenant_id'],
                'tenant_onboarding_invitation_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('auth_registration_invitations')
                ->restrictOnDelete();
            $table->index(['status', 'updated_at'], 'tenant_onboarding_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onboarding_states');
    }
};
