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
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            
            // Customer Type
            $table->enum('type', ['individual', 'business'])->default('individual');
            $table->string('code')->unique();
            
            // Basic Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number')->nullable(); // VAT/GST/TIN
            
            // Business Information (for business type)
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->integer('employee_count')->nullable();
            $table->date('established_date')->nullable();
            
            // Financial Information
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(0); // Net payment days
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer', 'cheque', 'other'])->default('cash');
            
            // Status and Classification
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended', 'blacklisted'])->default('active');
            $table->enum('priority', ['low', 'medium', 'high', 'vip'])->default('medium');
            $table->string('customer_group')->nullable(); // Retail, Wholesale, Distributor, etc.
            
            // Source and Assignment
            $table->string('source')->nullable(); // Website, Referral, Cold Call, etc.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Sales representative
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'organization_id', 'branch_id']);
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_group']);
            $table->index(['tenant_id', 'assigned_to']);
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
