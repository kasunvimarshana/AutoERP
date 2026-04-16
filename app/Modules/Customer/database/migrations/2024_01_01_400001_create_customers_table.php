<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete(); // for portal access
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->string('customer_code')->nullable();
            $table->string('name');
            $table->enum('type', ['individual', 'company'])->default('company');
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('credit_limit', 15, 4)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->foreignId('ar_account_id')->nullable(); // will reference accounts later
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            // Customers AR account
            $table->foreign('ar_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'customer_code']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};