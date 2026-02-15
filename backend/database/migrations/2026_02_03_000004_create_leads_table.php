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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            
            // Lead Information
            $table->string('code')->unique();
            $table->string('title'); // Lead title/description
            $table->enum('type', ['individual', 'business'])->default('individual');
            
            // Contact Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            
            // Company Information (for business leads)
            $table->string('company_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('industry')->nullable();
            $table->integer('company_size')->nullable();
            $table->string('website')->nullable();
            
            // Lead Details
            $table->enum('source', [
                'website', 'referral', 'social_media', 'email_campaign', 
                'cold_call', 'trade_show', 'advertisement', 'partner', 'other'
            ])->default('other');
            $table->string('source_details')->nullable();
            
            $table->enum('status', [
                'new', 'contacted', 'qualified', 'proposal', 
                'negotiation', 'won', 'lost', 'unqualified'
            ])->default('new');
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->integer('rating')->nullable(); // 1-5 rating
            
            // Sales Information
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->integer('probability')->nullable(); // 0-100 percentage
            $table->date('expected_close_date')->nullable();
            
            // Assignment and Stage
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage')->nullable(); // Custom pipeline stage
            
            // Conversion Tracking
            $table->boolean('is_converted')->default(false);
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Additional Information
            $table->text('description')->nullable();
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
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['tenant_id', 'is_converted']);
            $table->index(['expected_close_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
