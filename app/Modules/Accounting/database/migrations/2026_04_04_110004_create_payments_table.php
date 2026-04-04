<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference_number', 50)->unique();
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded, cancelled
            $table->string('method', 30); // cash, bank_transfer, card, cheque, credit
            $table->decimal('amount', 18, 4);
            $table->string('currency', 10)->default('USD');
            $table->string('payable_type', 100)->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
