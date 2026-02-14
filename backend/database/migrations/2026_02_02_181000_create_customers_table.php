<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->string('customer_code', 50)->unique();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('company_name', 200)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('tax_id', 100)->nullable()->comment('Tax/VAT ID');
            $table->string('registration_number', 100)->nullable();
            
            // Address fields
            $table->text('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_country', 100)->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_state', 100)->nullable();
            $table->string('shipping_country', 100)->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            
            // Financial settings
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->onDelete('set null');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(0)->comment('Net payment days');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            
            // Categorization
            $table->string('category', 50)->nullable();
            $table->string('group', 50)->nullable();
            $table->string('status', 50)->default('active');
            
            // CRM fields
            $table->string('lead_source', 100)->nullable();
            $table->string('sales_rep', 100)->nullable();
            $table->date('first_contact_date')->nullable();
            $table->date('last_contact_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'customer_code']);
            $table->index(['email', 'tenant_id']);
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
