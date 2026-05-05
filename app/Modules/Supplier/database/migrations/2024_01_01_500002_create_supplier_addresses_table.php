<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('supplier_id')->constrained(null, 'id', 'supplier_addresses_supplier_id_fk')->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping', 'remittance', 'other'])->default('billing');
            $table->string('label')->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->foreignId('country_id')->constrained('countries', 'id', 'supplier_addresses_country_id_fk');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['supplier_id', 'type'], 'supplier_addresses_supplier_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_addresses');
    }
};
