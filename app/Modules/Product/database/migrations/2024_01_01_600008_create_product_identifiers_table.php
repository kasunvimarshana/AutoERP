<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->morphs('identifiable'); // product, variant, batch, serial, location, etc.
            $table->enum('technology', [
                'barcode_1d', 'barcode_2d', 'qr_code',
                'rfid_hf', 'rfid_uhf', 'nfc', 'gs1_epc', 'custom'
            ])->default('barcode_1d');
            $table->enum('format', [
                'ean13', 'ean8', 'upc_a', 'code128', 'code39',
                'qr', 'datamatrix', 'gs1_128', 'epc_sgtin', 'other'
            ])->nullable();
            $table->string('value')->unique(); // The actual identifier string
            $table->string('gs1_company_prefix')->nullable();
            $table->json('gs1_application_identifiers')->nullable(); // Parsed AI data
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'identifiable_type', 'identifiable_id'], 'idx_prod_ident_tenant_morphable');
            $table->index(['tenant_id', 'value'], 'idx_prod_ident_tenant_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_identifiers');
    }
};