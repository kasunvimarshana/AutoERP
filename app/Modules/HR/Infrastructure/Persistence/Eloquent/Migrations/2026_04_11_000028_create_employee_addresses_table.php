<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_addresses', function (Blueprint $table) {
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

            $table->foreignId('employee_id')->constrained('employees', 'id')->cascadeOnDelete();
            $table->string('address_type', 40)->default('current');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 120);
            $table->string('state_province', 120)->nullable();
            $table->string('postal_code', 60)->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries', 'id')->nullOnDelete();
            $table->string('country_name', 120)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'address_type'], 'employee_addresses_type_idx');
            $table->index(['tenant_id', 'employee_id', 'is_primary'], 'employee_addresses_primary_idx');
            $table->index(['tenant_id', 'employee_id', 'is_active'], 'employee_addresses_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};
