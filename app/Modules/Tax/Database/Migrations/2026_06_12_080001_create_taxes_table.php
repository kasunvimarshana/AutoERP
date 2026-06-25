<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tax_type', 100);
            $table->string('calculation_method', 30);
            $table->boolean('is_withholding')->default(false);
            $table->boolean('recoverable')->default(false);
            $table->boolean('payable')->default(false);
            $table->boolean('receivable')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'taxes_tenant_code_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'taxes_scope_idx');
            $table->index(['tax_type', 'active'], 'taxes_type_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
