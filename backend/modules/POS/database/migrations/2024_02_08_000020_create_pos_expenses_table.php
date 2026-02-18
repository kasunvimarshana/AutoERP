<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('location_id');
            $table->uuid('category_id');
            $table->string('reference_number');
            $table->timestamp('expense_date');
            $table->decimal('amount', 15, 2);
            $table->uuid('payment_method_id')->nullable();
            $table->uuid('contact_id')->nullable(); // supplier/vendor
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('pos_business_locations')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('pos_expense_categories')->onDelete('restrict');
            $table->foreign('payment_method_id')->references('id')->on('pos_payment_methods')->onDelete('set null');
            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_expenses');
    }
};
