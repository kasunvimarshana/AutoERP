<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vehicle_service_invoice_links', 'settlement_mode')) {
            return;
        }
        Schema::table('vehicle_service_invoice_links', function (Blueprint $table): void {
            $table->string('settlement_mode', 20)->nullable()->after('invoice_total');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_invoice_links', function (Blueprint $table): void {
            $table->dropColumn('settlement_mode');
        });
    }
};
