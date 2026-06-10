<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('cheque_number', 100)->nullable()->after('reference_number');
            $table->date('cheque_date')->nullable()->after('cheque_number');
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('cheque_date')
                ->constrained('finance_accounts', 'id')
                ->nullOnDelete();
            $table->string('payee_name')->nullable()->after('bank_account_id');
            $table->text('amount_in_words')->nullable()->after('payee_name');

            $table->index('cheque_number', 'payments_cheque_number_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropIndex('payments_cheque_number_idx');
            $table->dropColumn([
                'cheque_number',
                'cheque_date',
                'bank_account_id',
                'payee_name',
                'amount_in_words',
            ]);
        });
    }
};
