<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->string('manual_job_card_number', 100)->nullable()->after('job_number');
            $table->decimal('next_service_mileage', 20, 6)->nullable()->after('odometer_reading');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->dropColumn('manual_job_card_number');
            $table->dropColumn('next_service_mileage');
        });
    }
};
