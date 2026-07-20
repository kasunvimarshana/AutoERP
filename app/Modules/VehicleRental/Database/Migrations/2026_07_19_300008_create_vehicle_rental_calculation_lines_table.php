<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_calculation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_calculation_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('calculation_id');
            $table->foreignId('rate_line_id');
            $table->unsignedInteger('line_number');
            $table->string('rate_code', 40);
            $table->string('unit', 20);
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_rate', 20, 6);
            $table->decimal('line_total', 20, 6);
            $table->boolean('is_taxable')->default(false);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['calculation_id', 'line_number'], 'vr_calculation_lines_calculation_line_uk');
            $table->unique(['calculation_id', 'rate_code'], 'vr_calculation_lines_calculation_code_uk');
            $table->unique(['id', 'tenant_id'], 'vr_calculation_lines_id_tenant_uk');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_calculation_lines_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['calculation_id', 'tenant_id'], 'vr_calculation_lines_calculation_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_calculations')->restrictOnDelete();
            $table->foreign(['rate_line_id', 'tenant_id'], 'vr_calculation_lines_rate_line_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_rate_lines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_calculation_lines');
    }
};
