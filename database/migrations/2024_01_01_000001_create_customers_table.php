<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Customers Table Migration
 *
 * Creates the customers table with multi-tenancy support.
 * Includes all necessary fields for customer management.
 */
return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->index();

            // Basic information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20);

            // Address information
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable();

            // Additional data
            $table->json('preferences')->nullable(); // Store customer preferences

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'phone']);
            $table->index(['first_name', 'last_name']);

            // Foreign keys (if using actual Laravel)
            // $table->foreign('tenant_id')
            //     ->references('id')
            //     ->on('tenants')
            //     ->onDelete('cascade');
        });
    }
};
