<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleService\Enums\VehicleServiceJobType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->string('type', 30)->default(VehicleServiceJobType::FullService->value)->after('expected_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};
