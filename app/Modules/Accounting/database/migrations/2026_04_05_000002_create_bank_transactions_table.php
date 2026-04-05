<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('bank_account_id');
            $table->date('transaction_date');
            $table->decimal('amount', 15, 4);
            $table->string('description');
            $table->string('type');               // debit|credit
            $table->string('status')->default('pending'); // pending|categorized|reconciled|excluded
            $table->unsignedBigInteger('expense_category_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('source')->default('import'); // manual|import|api
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tenant_id');
            $table->index('bank_account_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
