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
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('customer_code')->unique();
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            
            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('mobile')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('id_number')->nullable();
            
            // Address Information
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US');
            
            // Business Information
            $table->string('tax_id')->nullable();
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            
            // Status and Preferences
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->string('preferred_language', 5)->default('en');
            $table->json('preferences')->nullable();
            $table->json('metadata')->nullable();
            
            // Tracking
            $table->timestamp('last_service_date')->nullable();
            $table->decimal('lifetime_value', 10, 2)->default(0);
            $table->integer('total_services')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('customer_code');
            $table->index('email');
            $table->index('status');
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
