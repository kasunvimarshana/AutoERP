<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_maintenance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('vehicle_id')->index();
            $table->string('maintenance_type');
            $table->date('performed_at');
            $table->decimal('cost', 18, 8)->default('0.00000000');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->uuid('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_maintenance_records');
    }
};
