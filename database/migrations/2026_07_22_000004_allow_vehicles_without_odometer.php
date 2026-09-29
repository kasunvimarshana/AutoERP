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
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->decimal('odometer_reading', 20, 6)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('vehicles')->whereNull('odometer_reading')->exists()) {
            throw new \RuntimeException('Cannot restore a mandatory vehicle odometer while vehicles without readings exist.');
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->decimal('odometer_reading', 20, 6)->nullable(false)->default('0.000000')->change();
        });
    }
};
