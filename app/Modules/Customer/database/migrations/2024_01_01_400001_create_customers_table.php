<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('user_id')->nullable()->unique('customers_user_id_uk')->constrained(null, 'id', 'customers_user_id_fk')->nullOnDelete(); // for portal access
            $table->string('customer_code')->nullable();
            $table->string('name');
            $table->enum('type', ['individual', 'company'])->default('company');
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'customers_currency_id_fk')->nullOnDelete();
            $table->decimal('credit_limit', 20, 6)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->foreignId('ar_account_id')->nullable(); // accounts_receivable_account_id will reference accounts later
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            // Customers AR account
            $table->foreign('ar_account_id', 'customers_ar_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'customer_code'], 'customers_tenant_code_uk');
            $table->index(['tenant_id', 'name'], 'customers_tenant_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
