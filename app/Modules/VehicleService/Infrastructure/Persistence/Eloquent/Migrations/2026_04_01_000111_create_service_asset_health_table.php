<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_asset_health', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedTinyInteger('health_score');
            $table->dateTime('last_evaluated_at');
            $table->date('next_service_due_date')->nullable();
            $table->unsignedBigInteger('next_service_due_odometer')->nullable();
            $table->string('risk_level')->default('low');

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'service_asset_health_vehicle_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'service_asset_health_vehicle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_asset_health');
    }
};
