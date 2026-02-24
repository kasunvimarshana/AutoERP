<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_bank_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bank_account_id')->index();
            $table->string('type');
            $table->decimal('amount', 18, 8);
            $table->date('transaction_date');
            $table->string('description');
            $table->string('status')->default('unreconciled');
            $table->string('reference_number')->nullable();
            $table->uuid('journal_entry_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'bank_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bank_transactions');
    }
};
