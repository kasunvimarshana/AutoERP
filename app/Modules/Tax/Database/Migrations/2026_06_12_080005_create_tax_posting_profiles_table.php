<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_posting_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('tax_id');
            $table->string('direction', 50)->default('tax');
            $table->foreignId('account_id');
            $table->string('posting_key', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'tax_id', 'direction'], 'tax_posting_profiles_scope_tax_dir_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'tax_posting_profiles_scope_active_idx');

            $table->unique(['id', 'tenant_id'], 'tax_posting_profiles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'tax_posting_profiles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['tax_id', 'tenant_id'], 'tax_posting_profiles_tax_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('taxes')
                ->cascadeOnDelete();
            $table->foreign(['account_id', 'tenant_id'], 'tax_posting_profiles_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_posting_profiles');
    }
};
