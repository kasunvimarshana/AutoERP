<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->decimal('start_odometer', 20, 6)->nullable()->change();
            $table->decimal('end_odometer', 20, 6)->nullable()->change();
            $table->decimal('total_km', 20, 6)->nullable()->change();
            $table->decimal('garage_km', 20, 6)->nullable()->default(null)->change();
            $table->decimal('commercial_km', 20, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('vehicle_rental_running_charts')
            ->whereNull('start_odometer')
            ->orWhereNull('end_odometer')
            ->orWhereNull('total_km')
            ->orWhereNull('garage_km')
            ->orWhereNull('commercial_km')
            ->exists()) {
            throw new RuntimeException('Cannot restore mandatory rental odometers while charts without readings exist.');
        }

        Schema::table('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->decimal('start_odometer', 20, 6)->nullable(false)->change();
            $table->decimal('end_odometer', 20, 6)->nullable(false)->change();
            $table->decimal('total_km', 20, 6)->nullable(false)->change();
            $table->decimal('garage_km', 20, 6)->nullable(false)->default('0.000000')->change();
            $table->decimal('commercial_km', 20, 6)->nullable(false)->change();
        });
    }
};
