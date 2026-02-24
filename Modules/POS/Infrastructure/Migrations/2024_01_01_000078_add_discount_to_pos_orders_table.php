<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->uuid('discount_code_id')->nullable()->index()->after('currency');
            $table->decimal('discount_amount', 18, 8)->default(0)->after('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_code_id', 'discount_amount']);
        });
    }
};
