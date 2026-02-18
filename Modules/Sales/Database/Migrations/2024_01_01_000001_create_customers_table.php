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
            $table->unsignedBigInteger('tenant_id');
            $table->string('customer_number', 50)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->integer('payment_terms')->nullable()->comment('Payment terms in days');
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('customer_number');
            $table->index('email');
            $table->index('status');
            $table->index(['tenant_id', 'status']);
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
