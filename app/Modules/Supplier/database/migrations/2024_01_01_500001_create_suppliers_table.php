<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('user_id')->nullable()->unique('suppliers_user_id_uk')->constrained('users', 'id', 'suppliers_user_id_fk')->nullOnDelete(); // for portal access
            $table->string('supplier_code')->nullable();
            $table->string('name');
            $table->string('type')->default('company')->comment('individual, company');
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'suppliers_currency_id_fk')->nullOnDelete();
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->foreignId('ap_account_id')->nullable(); // accounts_payable_account_id will reference accounts later
            $table->string('status')->default('active')->comment('active, inactive');
            $table->text('notes')->nullable();
            $table->boolean('is_product_supplier')->default(true)->comment('Whether the supplier provides products.');
            $table->boolean('is_vehicle_lessor')->default(false)->comment('Whether the supplier is a vehicle lessor.');
            $table->json('metadata')->nullable();
            // Suppliers AP account
            $table->foreign('ap_account_id', 'suppliers_ap_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'supplier_code'], 'suppliers_tenant_code_uk');
            $table->index(['tenant_id', 'name'], 'suppliers_tenant_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
