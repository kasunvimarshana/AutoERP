<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('from_uom_id')->constrained('unit_of_measures');
            $table->foreignId('to_uom_id')->constrained('unit_of_measures');
            $table->decimal('conversion_factor', 20, 6);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'from_uom_id', 'to_uom_id'],
                'uom_conversions_from_to_uk'
            );
            $table->index('tenant_id', 'uom_conversions_tenant_idx');
            $table->index('organization_unit_id', 'uom_conversions_org_idx');
            $table->index('from_uom_id', 'uom_conversions_from_uom_idx');
            $table->index('to_uom_id', 'uom_conversions_to_uom_idx');
            $table->index(['tenant_id', 'is_active'], 'uom_conversions_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
