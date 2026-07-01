<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_account_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('account_role_id');
            $table->unsignedBigInteger('account_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'finance_account_assignments_id_tenant_uk');
            $table->index(
                ['tenant_id', 'organization_unit_id', 'account_role_id', 'effective_from', 'effective_to'],
                'finance_account_assignments_effective_ix',
            );

            $table->foreign('tenant_id', 'finance_account_assignments_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'finance_account_assignments_org_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(
                ['account_role_id', 'tenant_id'],
                'finance_account_assignments_role_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('finance_account_roles')
                ->restrictOnDelete();
            $table->foreign(
                ['account_id', 'tenant_id'],
                'finance_account_assignments_account_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(
                ['created_by', 'tenant_id'],
                'finance_account_assignments_created_by_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(
                ['ended_by', 'tenant_id'],
                'finance_account_assignments_ended_by_tenant_fk',
            )
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_account_assignments');
    }
};
