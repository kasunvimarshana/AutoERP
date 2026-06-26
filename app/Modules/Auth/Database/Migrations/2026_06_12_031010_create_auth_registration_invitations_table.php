<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_registration_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('auth_registration_invites_public_uk');
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('email');
            $table->char('token_hash', 64)->unique('auth_registration_invites_token_uk');
            $table->text('delivery_token')->nullable();
            $table->string('purpose', 50);
            $table->string('status', 30)->default('pending');
            $table->dateTime('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_by_user_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'auth_registration_invites_id_tenant_uk');
            $table->index(['tenant_id', 'user_id', 'purpose', 'status'], 'auth_registration_invites_user_status_idx');
            $table->foreign(['user_id', 'tenant_id'], 'auth_reg_invites_target_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'auth_reg_invites_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['role_id', 'tenant_id'], 'auth_reg_invites_role_tenant_fk')
                ->references(['id', 'tenant_id'])->on('roles')->restrictOnDelete();
            $table->foreign(['accepted_by_user_id', 'tenant_id'], 'auth_reg_invites_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->index(['tenant_id', 'email', 'status', 'expires_at'], 'auth_registration_invites_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_registration_invitations');
    }
};
