<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // ISO 4217 currency code (USD, EUR, GBP, etc.)
            $table->string('name'); // Currency name (US Dollar, Euro, etc.)
            $table->string('symbol', 10); // Currency symbol ($, €, £, etc.)
            $table->integer('decimal_places')->default(2); // Number of decimal places
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // One currency should be default
            $table->decimal('exchange_rate', 20, 8)->default(1.00000000); // Exchange rate to base currency
            $table->timestamp('exchange_rate_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
