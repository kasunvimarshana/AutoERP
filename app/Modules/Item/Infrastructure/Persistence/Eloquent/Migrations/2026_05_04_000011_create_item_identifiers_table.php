<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_identifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->string('technology')->default('barcode_1d')->comment('barcode_1d, barcode_2d, qr_code, rfid_hf, rfid_uhf, nfc, gs1_epc');
            $table->string('format')->nullable()->comment('ean13, ean8, upc_a, code128, code39, qr, datamatrix, etc.');
            $table->string('value')->comment('the actual identifier string');
            $table->string('gs1_company_prefix')->nullable();
            $table->json('gs1_application_identifiers')->nullable();   // parsed AI data (kept as string in DB‑agnostic implementation)
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'item_id', 'variant_id'], 'item_identifiers_item_variant_idx');
            $table->index(['tenant_id', 'value'], 'item_identifiers_value_idx');
            $table->index(['tenant_id', 'is_active', 'value'], 'item_identifiers_active_value_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_identifiers');
    }
};
