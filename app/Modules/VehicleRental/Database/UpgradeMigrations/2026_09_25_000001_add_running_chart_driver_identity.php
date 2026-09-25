<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->string('driver_identity_source')->nullable()->after('night_outs');
            $table->unsignedBigInteger('driver_employee_id')->nullable()->after('driver_identity_source');
            $table->string('driver_name_snapshot')->nullable()->after('driver_employee_id');
            $table->string('driver_reference_snapshot')->nullable()->after('driver_name_snapshot');
            $table->index(['tenant_id', 'driver_employee_id', 'starts_at', 'ends_at'], 'vrc_driver_employee_period_ix');
            $table->index(['tenant_id', 'driver_identity_source', 'driver_reference_snapshot', 'starts_at', 'ends_at'], 'vrc_driver_external_period_ix');
            $table->foreign(['driver_employee_id', 'tenant_id'], 'vrc_driver_employee_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->dropForeign('vrc_driver_employee_fk');
            $table->dropIndex('vrc_driver_employee_period_ix');
            $table->dropIndex('vrc_driver_external_period_ix');
            $table->dropColumn(['driver_identity_source', 'driver_employee_id', 'driver_name_snapshot', 'driver_reference_snapshot']);
        });
    }
};
