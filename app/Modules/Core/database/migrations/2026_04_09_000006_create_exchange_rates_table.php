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
            $table->foreignId('from_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('to_currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('rate', 20, 8);
            $table->date('effective_date');
            $table->string('source', 50)->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'from_currency_id', 'to_currency_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
