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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping', 'remittance', 'other'])->default('billing');
            $table->string('label')->nullable();
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->foreignId('country_id')->constrained('countries');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['supplier_id', 'type'], 'idx_supplier_addresses_supplier_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_addresses');
    }
};