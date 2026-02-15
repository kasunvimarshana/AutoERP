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
        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->decimal('base_amount', 15, 4);
            $table->decimal('tax_amount', 15, 4);
            $table->decimal('total_amount', 15, 4);
            $table->boolean('is_inclusive')->default(false);
            $table->json('tax_breakdown')->nullable();
            $table->json('applied_taxes')->nullable();
            $table->json('exemptions_applied')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->index();
            $table->foreignId('branch_id')->nullable()->index();
            $table->foreignId('tax_jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
            $table->string('calculation_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'calculated_at']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
    }
};
