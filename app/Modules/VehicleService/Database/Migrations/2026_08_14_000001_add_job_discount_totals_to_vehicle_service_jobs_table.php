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
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->decimal('line_discount_total', 20, 6)->default('0.000000');
            $table->decimal('job_discount_base', 20, 6)->default('0.000000');
            $table->decimal('job_discount_amount', 20, 6)->default('0.000000');
        });

        DB::table('vehicle_service_jobs')->update([
            'line_discount_total' => DB::raw('discount_total'),
            'job_discount_base' => DB::raw('subtotal - discount_total'),
        ]);
    }

    public function down(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->dropColumn('line_discount_total');
            $table->dropColumn('job_discount_base');
            $table->dropColumn('job_discount_amount');
        });
    }
};
