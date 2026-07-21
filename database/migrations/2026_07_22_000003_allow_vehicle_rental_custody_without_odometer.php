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
        Schema::table('vehicle_rental_custody_events', function (Blueprint $table): void {
            $table->decimal('odometer', 20, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('vehicle_rental_custody_events')->whereNull('odometer')->exists()) {
            throw new RuntimeException('Cannot restore mandatory custody odometers while events without readings exist.');
        }

        Schema::table('vehicle_rental_custody_events', function (Blueprint $table): void {
            $table->decimal('odometer', 20, 6)->nullable(false)->change();
        });
    }
};
