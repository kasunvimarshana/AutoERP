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

        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_id')->constrained('taxes', 'id')->cascadeOnDelete();
            $table->decimal('rate', 20, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tax_id', 'active', 'effective_from'], 'tax_rates_tax_active_from_idx');
            $table->index(['effective_from', 'effective_to'], 'tax_rates_dates_idx');
        });

        Schema::create('tax_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'tax_groups_scope_code_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'is_default'], 'tax_groups_default_idx');
        });

        Schema::create('tax_group_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_group_id')->constrained('tax_groups', 'id')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('taxes', 'id')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tax_group_id', 'sequence'], 'tax_group_lines_group_sequence_uk');
            $table->unique(['tax_group_id', 'tax_id'], 'tax_group_lines_group_tax_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_lines');
        Schema::dropIfExists('tax_groups');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('taxes');
    }
};
