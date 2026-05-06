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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedTinyInteger('health_score');
            $table->dateTime('last_evaluated_at');
            $table->date('next_service_due_date')->nullable();
            $table->unsignedBigInteger('next_service_due_odometer')->nullable();
            $table->json('component_health')->nullable();
            $table->enum('risk_level', ['low','medium','high','critical'])->default('low');
            $table->timestamps();
            $table->index(['tenant_id','vehicle_id'], 'asset_health_vehicle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_asset_health');
    }
};
