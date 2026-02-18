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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_number', 100)->nullable();

            // Billing address
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_postal_code', 20)->nullable();

            // Shipping address
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();

            // Business terms
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tenant_id');
            $table->index('customer_type');
            $table->index('email');
            $table->index('is_active');
            $table->unique(['tenant_id', 'email']);
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
