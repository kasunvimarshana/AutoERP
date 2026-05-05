<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'journal_entry_lines_tenant_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->constrained(null, 'id', 'journal_entry_lines_journal_entry_id_fk')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained(null, 'id', 'journal_entry_lines_account_id_fk')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit_amount', 20, 6)->default(0);
            $table->decimal('credit_amount', 20, 6)->default(0);
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'journal_entry_lines_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->decimal('base_debit_amount', 20, 6)->default(0);
            $table->decimal('base_credit_amount', 20, 6)->default(0);
            // $table->foreignId('cost_center_id')->nullable()->constrained('org_units', 'id', 'journal_entry_lines_cost_center_id_fk')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers', 'id', 'journal_entry_lines_cost_center_id_fk')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'journal_entry_id'], 'journal_entry_lines_account_entry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
