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
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('from_currency_id');
            $table->unsignedBigInteger('to_currency_id');
            $table->decimal('rate', 20, 8);
            $table->date('effective_date');
            $table->string('source', 50)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('from_currency_id')->references('id')->on('currencies')->cascadeOnDelete();
            $table->foreign('to_currency_id')->references('id')->on('currencies')->cascadeOnDelete();

            $table->unique(['tenant_id', 'from_currency_id', 'to_currency_id', 'effective_date'], 'unique_exchange_rate');
            $table->index(['tenant_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};