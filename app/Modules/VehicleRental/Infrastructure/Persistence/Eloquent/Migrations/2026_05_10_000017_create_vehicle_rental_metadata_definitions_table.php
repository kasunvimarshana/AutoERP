<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_metadata_definitions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('entity_type');
            $table->string('field_key');
            $table->string('label');
            $table->string('data_type');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
            $table->unique(['tenant_id', 'entity_type', 'field_key'], 'vehicle_rental_metadata_definitions_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_metadata_definitions');
    }
};
