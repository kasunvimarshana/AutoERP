<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('name');
            $table->string('account_number', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('swift', 20)->nullable();
            $table->enum('type', ['bank', 'credit_card', 'cash', 'other']);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
