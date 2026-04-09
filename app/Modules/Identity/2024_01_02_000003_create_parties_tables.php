<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parties — unified entity for customers, suppliers, employees, partners.
     * Eliminates redundant customer/supplier tables; mirrors SAP Business Partner model.
     */
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('type', ['customer', 'supplier', 'both', 'employee', 'partner', 'other']);
            $table->string('code', 50);
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_number', 100)->nullable();       // VAT, GST, EIN, etc.
            $table->string('registration_no', 100)->nullable();  // Company reg number
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('credit_limit', 18, 4)->nullable();
            $table->integer('payment_terms_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('party_id');
            $table->enum('type', ['billing', 'shipping', 'both'])->default('both');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('party_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->index(['party_id', 'type']);
        });

        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('party_id');
            $table->string('name');
            $table->string('role', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('party_id')->references('id')->on('parties')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_contacts');
        Schema::dropIfExists('party_addresses');
        Schema::dropIfExists('parties');
    }
};
