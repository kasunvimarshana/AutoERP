<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('customer_id')->constrained('customers', 'id')->cascadeOnDelete();
            $table->string('tax_registration_number', 120)->nullable();
            $table->string('vat_number', 120)->nullable();
            $table->unsignedBigInteger('tax_group_id')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->string('exemption_certificate_reference', 120)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'customer_id'], 'customer_tax_profiles_customer_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tax_profiles');
    }
};