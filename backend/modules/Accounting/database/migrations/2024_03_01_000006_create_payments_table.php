<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('payment_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();

            $table->string('payment_method', 30);
            $table->string('status', 30)->default('pending');
            $table->timestamp('payment_date');
            $table->decimal('amount', 19, 4);
            $table->string('currency_code', 3)->default('USD');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
