<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('name')->comment('e.g., Monthly Rent, Annual Insurance');
            $table->string('type')->default('expense')->comment('expense, income');
            $table->string('sub_type')->nullable()->comment('electricity, phone, rent, commission, misc_income, interest');
            $table->string('party_type')->nullable()->comment('customer, supplier, employee, other');
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->comment('primary expense/income account');
            $table->foreignId('contra_account_id')->constrained('accounts')->comment('bank/cash/payable');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete()->comment('applied tax rate');
            $table->decimal('tax_rate', 20, 4)->default(0)->comment('cached tax rate for historical accuracy');
            $table->decimal('amount', 20, 4);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4);
            $table->string('frequency')->default('monthly')->comment('daily, weekly, monthly, quarterly, yearly');
            $table->unsignedInteger('interval')->default(1)->comment('every X frequency (e.g., every 3 months)');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_vouchers');
    }
};
