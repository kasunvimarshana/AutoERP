<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');

            $table->string('vehicle_code', 60);
            $table->string('registration_number', 100);
            $table->string('chassis_number', 100)->nullable();
            $table->string('engine_number', 100)->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission_type')->nullable();
            $table->string('ownership_type')->nullable();
            $table->string('status', 60)->default('active')->comment('active, inactive');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'vehicle_code'], 'vehicles_vehicle_code_uk');
            $table->unique(['tenant_id', 'registration_number'], 'vehicles_registration_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicles_organization_unit_idx');
            $table->index(['tenant_id', 'status'], 'vehicles_status_idx');
            $table->index(['tenant_id', 'make', 'model'], 'vehicles_make_model_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
