<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_rental_calculations', function (Blueprint $table): void {
            $table->decimal('commercial_km', 20, 6)->nullable()->change();
            $table->decimal('excess_km', 20, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('vehicle_rental_calculations')
            ->whereNull('commercial_km')
            ->orWhereNull('excess_km')
            ->exists()) {
            throw new \RuntimeException('Cannot restore mandatory rental distance totals while unmeasured calculations exist.');
        }

        Schema::table('vehicle_rental_calculations', function (Blueprint $table): void {
            $table->decimal('commercial_km', 20, 6)->nullable(false)->change();
            $table->decimal('excess_km', 20, 6)->nullable(false)->change();
        });
    }
};
