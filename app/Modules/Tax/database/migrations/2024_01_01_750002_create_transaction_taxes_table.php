<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('tax_rate_id')->constrained('tax_rates');
            $table->decimal('taxable_amount', 15, 4);
            $table->decimal('tax_amount', 15, 4);
            $table->foreignId('tax_account_id')->constrained('accounts');
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_taxes');
    }
};
