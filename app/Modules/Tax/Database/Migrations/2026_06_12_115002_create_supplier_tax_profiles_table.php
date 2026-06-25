<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->cascadeOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('exemption_status', 50)->default('taxable');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('supplier_id', 'supplier_tax_profiles_supplier_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'supplier_tax_profiles_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_tax_profiles');
    }
};
