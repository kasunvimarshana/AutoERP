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
            $table->decimal('next_service_mileage', 20, 6)->nullable();
            $table->string('manual_job_card', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->dropColumn('next_service_mileage');
            $table->dropColumn('manual_job_card');
        });
    }
};
