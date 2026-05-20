<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('voucher_number');
            $table->string('type')->default('expense')->comment('expense, income');
            $table->string('sub_type')->nullable()->comment('electricity, phone, rent, commission, misc_income, interest');
            $table->date('voucher_date');
            $table->date('due_date')->nullable();
            $table->string('party_type')->nullable()->comment('customer, supplier, employee, other');
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('reference')->nullable()->comment('bill number, etc.');
            $table->text('description')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->comment('primary expense/income account');
            $table->foreignId('contra_account_id')->constrained('accounts')->comment('bank/cash/payable account');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete()->comment('applied tax rate');
            $table->decimal('tax_rate', 20, 4)->default(0)->comment('cached tax rate for historical accuracy');
            $table->decimal('amount', 20, 4);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4);
            $table->string('status')->default('draft')->comment('draft, posted, paid, void');
            // $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'voucher_number'], 'vouchers_voucher_number_uk');
            $table->index(['tenant_id', 'type', 'voucher_date'], 'vouchers_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
