<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('account_id')->nullable(); // linked Chart-of-Accounts entry
            $table->string('name', 200);
            $table->string('account_number', 100)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('bank_code', 50)->nullable();
            // checking, savings, credit_card, line_of_credit
            $table->string('account_type', 30)->default('checking');
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('opening_balance', 15, 4)->default(0);
            $table->decimal('current_balance', 15, 4)->default(0);
            $table->decimal('credit_limit', 15, 4)->default(0);
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
