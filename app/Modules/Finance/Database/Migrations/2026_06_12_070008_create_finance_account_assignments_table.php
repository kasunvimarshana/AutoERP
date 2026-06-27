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
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_account_assignments_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('account_role_id');
            $table->foreignId('account_id');
            $table->string('context_type', 100)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->char('scope_key', 64);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'scope_key', 'effective_from'], 'finance_account_assignments_scope_start_uk');
            $table->unique(['id', 'tenant_id'], 'finance_account_assignments_id_tenant_uk');
            $table->index(['tenant_id', 'account_role_id', 'is_active'], 'finance_account_assignments_role_active_ix');
            $table->index(['tenant_id', 'context_type', 'context_id'], 'finance_account_assignments_context_ix');
            $table->index(['effective_from', 'effective_to'], 'finance_account_assignments_effective_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_account_assignments_ou_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['account_role_id', 'tenant_id'], 'finance_account_assignments_role_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_account_roles')
                ->restrictOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'finance_account_assignments_account_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'finance_account_assignments_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'finance_account_assignments_updated_by_tenant_fk')
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
