<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->decimal('promotion_discount_amount', 20, 6)->default('0.000000');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->dropColumn('promotion_discount_amount');
        });
    }
};
