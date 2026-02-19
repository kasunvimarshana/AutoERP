<?php

declare(strict_types=1);

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
        Schema::create('vendors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->string('vendor_code', 50)->unique();
            $table->string('name', 200);
            $table->string('contact_person', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->integer('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 20, 6)->nullable();
            $table->decimal('current_balance', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vendor_code']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'email']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
