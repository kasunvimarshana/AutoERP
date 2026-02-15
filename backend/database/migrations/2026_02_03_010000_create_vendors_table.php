<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['individual', 'business'])->default('business');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('company_name')->nullable();
            $table->string('industry', 100)->nullable();
            $table->date('established_date')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('payment_terms_days')->nullable();
            $table->enum('payment_terms_type', ['net', 'eom', 'cod', 'advance'])->nullable();
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('payment_method', ['cash', 'credit_card', 'bank_transfer', 'cheque', 'other'])->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended', 'blacklisted'])->default('active');
            $table->integer('rating')->nullable();
            $table->string('vendor_category', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
