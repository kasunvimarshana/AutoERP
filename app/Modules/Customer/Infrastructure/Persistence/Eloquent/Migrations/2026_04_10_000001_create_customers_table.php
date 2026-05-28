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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('customer_code', 60);
            $table->string('customer_name', 180);
            $table->string('legal_name', 180)->nullable();
            $table->string('display_name', 180)->nullable();
            $table->string('customer_type', 60)->default('business')->comment('business, individual, government, other');
            $table->foreignId('category_id')->nullable()->constrained('customer_categories', 'id')->nullOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('mobile', 100)->nullable();
            $table->string('website')->nullable();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->foreignId('default_payment_term_id')->nullable()->constrained('payment_terms', 'id')->nullOnDelete();
            $table->foreignId('default_receivable_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->foreignId('default_income_account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete();
            $table->decimal('credit_limit', 20, 4)->nullable();
            $table->unsignedInteger('credit_days')->nullable();
            $table->boolean('credit_hold')->default(false);
            $table->string('status', 60)->default('draft')->comment('draft, pending_approval, active, inactive, blocked, suspended, archived');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->unsignedBigInteger('unblocked_by')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->unsignedBigInteger('credit_hold_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('credit_hold_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'customer_code'], 'customers_customer_code_uk');
            $table->unique(['tenant_id', 'registration_number'], 'customers_registration_number_uk');
            $table->index(['tenant_id', 'customer_name'], 'customers_customer_name_idx');
            $table->index(['tenant_id', 'status', 'is_active'], 'customers_status_active_idx');
            $table->index(['tenant_id', 'email'], 'customers_email_idx');
            $table->index(['tenant_id', 'credit_hold'], 'customers_credit_hold_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
