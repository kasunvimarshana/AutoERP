<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\RunningChartFields;

return new class extends Migration
{
    private const TABLE = 'vehicle_rental_running_charts';

    private const EMPLOYEE_FK = 'vrc_driver_employee_tenant_fk';

    private const EMPLOYEE_TIMELINE_INDEX = 'vrc_driver_employee_timeline_ix';

    private const EXTERNAL_TIMELINE_INDEX = 'vrc_driver_external_timeline_ix';

    public function up(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->string('driver_identity_source')->nullable();
            $table->unsignedBigInteger('driver_employee_id')->nullable();
            $table->string('driver_name_snapshot', RunningChartFields::DRIVER_REFERENCE_LENGTH)->nullable();
            $table->string('driver_reference_snapshot', RunningChartFields::DRIVER_REFERENCE_LENGTH)->nullable();

            $table->foreign(['driver_employee_id', 'tenant_id'], self::EMPLOYEE_FK)
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();

            $table->index(
                ['tenant_id', 'driver_identity_source', 'driver_employee_id', 'status', 'starts_at'],
                self::EMPLOYEE_TIMELINE_INDEX,
            );
            $table->index(
                ['tenant_id', 'driver_identity_source', 'driver_reference_snapshot', 'status', 'starts_at'],
                self::EXTERNAL_TIMELINE_INDEX,
            );
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropIndex(self::EXTERNAL_TIMELINE_INDEX);
            $table->dropIndex(self::EMPLOYEE_TIMELINE_INDEX);
            $table->dropForeign(self::EMPLOYEE_FK);
            $table->dropColumn([
                'driver_identity_source',
                'driver_employee_id',
                'driver_name_snapshot',
                'driver_reference_snapshot',
            ]);
        });
    }
};
