<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_rate_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_rate_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('rate_version_id');
            $table->unsignedInteger('line_number');
            $table->string('rate_code', 40);
            $table->string('unit', 20);
            $table->decimal('rate', 20, 6);
            $table->boolean('is_taxable')->default(false);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['rate_version_id', 'line_number'], 'vr_rate_lines_version_line_uk');
            $table->unique(['rate_version_id', 'rate_code'], 'vr_rate_lines_version_code_uk');
            $table->unique(['id', 'tenant_id'], 'vr_rate_lines_id_tenant_uk');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_rate_lines_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['rate_version_id', 'tenant_id'], 'vr_rate_lines_version_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_rate_versions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_rate_lines');
    }
};
