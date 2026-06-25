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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('tax_id')->constrained('taxes', 'id')->cascadeOnDelete();
            $table->string('direction', 50)->default('tax');
            $table->foreignId('account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->string('posting_key', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'tax_id', 'direction'], 'tax_posting_profiles_scope_tax_dir_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'tax_posting_profiles_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_posting_profiles');
    }
};
