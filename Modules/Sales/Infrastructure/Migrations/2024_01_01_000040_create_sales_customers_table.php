<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('type')->default('b2b');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('credit_limit', 18, 8)->default(0);
            $table->string('status')->default('active');
            $table->uuid('price_list_id')->nullable()->index();
            $table->string('payment_terms')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('tax_id')->nullable();
            $table->json('billing_address')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('sales_customers'); }
};
