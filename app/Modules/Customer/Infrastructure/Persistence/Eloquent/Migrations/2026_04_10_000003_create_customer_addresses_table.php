<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
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

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('address_type')->default('billing')->comment('billing, shipping, office, home, other');
            $table->string('label')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code');
            $table->foreignId('country_id')->nullable()->constrained('countries', 'id')->nullOnDelete();
            $table->string('country_name')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_primary_billing')->default(false);
            $table->boolean('is_primary_shipping')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('geo_lat', 20, 4)->nullable();
            $table->decimal('geo_lng', 20, 4)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id', 'address_type'], 'customer_addresses_type_idx');
            $table->index(['tenant_id', 'customer_id', 'is_primary_billing'], 'customer_addresses_primary_billing_idx');
            $table->index(['tenant_id', 'customer_id', 'is_primary_shipping'], 'customer_addresses_primary_shipping_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
