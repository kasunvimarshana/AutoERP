<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('customer_code', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('company_name')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('payment_terms', 50)->nullable(); // net30, net60, etc
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('customer_type', 50)->default('standard'); // standard, vip, wholesale, retail
            $table->string('status', 50)->default('active'); // active, inactive, blocked
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('customer_code');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
