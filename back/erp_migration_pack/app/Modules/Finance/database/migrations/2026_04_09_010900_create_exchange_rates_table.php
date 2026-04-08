<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('base_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('quote_currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('rate', 18, 8);
            $table->string('rate_source')->nullable();
            $table->unique(['tenant_id', 'base_currency_id', 'quote_currency_id', 'rate_date']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
