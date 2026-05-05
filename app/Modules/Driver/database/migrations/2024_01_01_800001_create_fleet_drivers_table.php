<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('employee_id')->nullable()->constrained('employees', 'id', 'fdrv_employee_fk')->nullOnDelete();
            $table->string('driver_code');
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('compensation_type', ['salary', 'per_trip', 'commission'])->default('salary');
            $table->decimal('per_trip_rate', 20, 6)->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->enum('status', ['available', 'on_trip', 'suspended', 'off_duty'])->default('available');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'driver_code'], 'fleet_drivers_tenant_ou_code_uk');
            $table->index(['tenant_id', 'status'], 'fleet_drivers_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_drivers');
    }
};
