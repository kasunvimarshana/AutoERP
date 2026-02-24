<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('logistics_delivery_orders', function (Blueprint $table) {
            $table->uuid('source_location_id')->nullable()->after('carrier_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_delivery_orders', function (Blueprint $table) {
            $table->dropColumn('source_location_id');
        });
    }
};
