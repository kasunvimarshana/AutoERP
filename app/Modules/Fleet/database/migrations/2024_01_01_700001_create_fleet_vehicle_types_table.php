<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicle_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('base_daily_rate', 20, 6)->default(0);
            $table->decimal('base_hourly_rate', 20, 6)->default(0);
            $table->integer('seating_capacity')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'name'], 'fleet_vehicle_types_tenant_ou_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicle_types');
    }
};
