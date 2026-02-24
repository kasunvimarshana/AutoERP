<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('from_currency_code', 3);
            $table->string('to_currency_code', 3);
            $table->decimal('rate', 18, 8)->default('0.00000000');
            $table->string('source', 20)->default('manual');
            $table->date('effective_date');
            $table->timestamps();

            $table->index(['tenant_id', 'from_currency_code', 'to_currency_code', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
