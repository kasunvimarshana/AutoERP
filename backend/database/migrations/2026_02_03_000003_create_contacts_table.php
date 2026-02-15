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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            // Contact Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            
            // Professional Information
            $table->string('designation')->nullable(); // Job Title
            $table->string('department')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_decision_maker')->default(false);
            
            // Contact Preferences
            $table->boolean('email_opt_in')->default(true);
            $table->boolean('sms_opt_in')->default(true);
            $table->boolean('phone_opt_in')->default(true);
            $table->string('preferred_contact_method')->nullable(); // email, phone, sms
            $table->string('preferred_contact_time')->nullable();
            
            // Social Media
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_handle')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->date('birthday')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'email']);
            $table->index(['customer_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
