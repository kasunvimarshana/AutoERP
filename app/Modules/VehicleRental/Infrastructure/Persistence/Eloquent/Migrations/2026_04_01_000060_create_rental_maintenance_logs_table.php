<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->date('service_date');
            $table->unsignedBigInteger('service_odometer')->nullable();
            $table->string('service_type');
            $table->text('description');
            $table->decimal('cost', 20, 4)->default(0);
            $table->string('vendor')->nullable();
            $table->string('status')->default('completed');
            $table->date('next_service_due_date')->nullable();
            $table->unsignedBigInteger('next_service_due_odometer')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id', 'service_date'], 'rental_maintenance_logs_vehicle_service_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_maintenance_logs');
    }
};
