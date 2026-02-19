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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('organization_number', 50)->unique()->comment('Unique organization identifier');
            $table->string('name')->index()->comment('Organization name');
            $table->string('legal_name')->nullable()->comment('Legal/registered business name');
            $table->enum('type', ['single', 'multi_branch', 'franchise'])->default('single')->index()->comment('Organization type');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index()->comment('Organization status');

            // Tax and registration information
            $table->string('tax_id', 100)->nullable()->unique()->comment('Tax identification number');
            $table->string('registration_number', 100)->nullable()->unique()->comment('Business registration number');

            // Contact information
            $table->string('email')->nullable()->index()->comment('Organization email');
            $table->string('phone', 20)->nullable()->comment('Organization phone');
            $table->string('website')->nullable()->comment('Organization website');

            // Address information
            $table->text('address')->nullable()->comment('Street address');
            $table->string('city')->nullable()->index()->comment('City');
            $table->string('state', 100)->nullable()->index()->comment('State/Province');
            $table->string('postal_code', 20)->nullable()->comment('Postal/ZIP code');
            $table->string('country', 2)->nullable()->index()->comment('Country code (ISO 3166-1 alpha-2)');

            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional JSON metadata');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['status', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
