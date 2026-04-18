<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'product_identifiers_tenant_id_fk')->cascadeOnDelete();
            // $table->morphs('identifiable'); // product, variant, batch, serial, location, etc.
            $table->foreignId('product_id')->constrained('products', 'id', 'product_identifiers_product_id_fk');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'product_identifiers_variant_id_fk');
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'product_identifiers_batch_id_fk');
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'product_identifiers_serial_id_fk');
            // $table->enum('identifier_type', ['barcode', 'qr', 'rfid', 'nfc', 'epc', 'gtin', 'gln', 'sscc', 'custom']);
            // $table->string('identifier_value');
            $table->enum('technology', [
                'barcode_1d', 'barcode_2d', 'qr_code',
                'rfid_hf', 'rfid_uhf', 'nfc', 'gs1_epc', 'custom',
            ])->default('barcode_1d');
            $table->enum('format', [
                'ean13', 'ean8', 'upc_a', 'code128', 'code39',
                'qr', 'datamatrix', 'gs1_128', 'epc_sgtin', 'other',
            ])->nullable();
            $table->string('value'); // The actual identifier string
            $table->string('gs1_company_prefix')->nullable();
            $table->json('gs1_application_identifiers')->nullable(); // Parsed AI data
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('format_config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'variant_id'], 'product_identifiers_tenant_product_variant_idx');
            $table->index(['tenant_id', 'value'], 'product_identifiers_tenant_value_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_identifiers');
    }
};
