<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // GS1 Global Trade Item Number (GTIN-8, GTIN-12, GTIN-13, GTIN-14)
            $table->string('gtin', 14)->nullable()->unique()->after('barcode');
            // GS1 Company Prefix
            $table->string('gs1_company_prefix', 12)->nullable()->after('gtin');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gtin', 'gs1_company_prefix']);
        });
    }
};
