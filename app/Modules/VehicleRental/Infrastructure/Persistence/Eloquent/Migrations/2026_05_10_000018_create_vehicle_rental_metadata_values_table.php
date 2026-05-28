<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_metadata_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('metadata_definition_id')
                ->constrained('vehicle_rental_metadata_definitions')
                ->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['metadata_definition_id', 'entity_type', 'entity_id'], 'vehicle_rental_metadata_values_uk');
            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'vehicle_rental_metadata_values_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_metadata_values');
    }
};
