<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traceability & AIDC Module
     *
     * Provides a UNIFIED, technology-agnostic interface for all Automatic
     * Identification and Data Capture (AIDC) technologies:
     *
     *   - 1D Barcodes: Code128, EAN-13, EAN-8, UPC-A, UPC-E, ITF, Code39
     *   - 2D Barcodes: QR Code, Data Matrix, PDF417, Aztec
     *   - RFID: HF (ISO 15693), UHF (ISO 18000-6C / EPC Gen2)
     *   - NFC: ISO 14443
     *   - GS1: EPC, SGTIN, SSCC, GRAI, GIAI, GSRN, GDTI
     *   - EPCIS: Electronic Product Code Information Services (GS1 standard)
     *
     * identifiers: polymorphic — attaches to any entity (product, variant,
     *   batch, serial, location, transaction, etc.)
     *
     * trace_logs: immutable append-only audit ledger for full forward and
     *   backward traceability (essential for DSCSA pharma compliance,
     *   EPCIS logistics, GS1 retail, manufacturing tool tracking)
     *
     * scan_sessions: groups scan events to a WMS operation (receive, pick,
     *   pack, ship, count, transfer)
     */
    public function up(): void
    {
        // ── Identifiers (Unified AIDC Entity) ─────────────────────────────────
        Schema::create('identifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            // Polymorphic: product, product_variant, batch, serial_number,
            //              location, purchase_order, delivery_order, etc.
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');

            $table->enum('identifier_type', [
                'barcode_1d',     // generic 1D
                'barcode_2d',     // generic 2D
                'qr',             // QR Code
                'rfid_hf',        // HF RFID (NFC/RFID HF ISO 15693)
                'rfid_uhf',       // UHF RFID (EPC Gen2 ISO 18000-6C)
                'nfc',            // NFC (ISO 14443)
                'epc_gs1',        // GS1 EPC (SGTIN, SSCC, GRAI, etc.)
                'ean',            // EAN-8 / EAN-13
                'upc',            // UPC-A / UPC-E
                'code128',        // Code 128
                'datamatrix',     // Data Matrix (GS1 DI)
                'custom',         // tenant-defined type
            ]);

            $table->string('value', 500);          // the actual identifier value / code
            $table->unique('value');               // globally unique across all identifiers

            // GS1-specific fields
            $table->json('gs1_application_identifiers')->nullable();  // AI table: {01: GTIN, 10: batch, 17: expiry}
            $table->string('epc_uri', 500)->nullable();               // urn:epc:id:sgtin:...

            $table->string('format', 50)->nullable();    // e.g. EAN-13, QR_CODE, UHF_EPC_GEN2
            $table->boolean('is_primary')->default(false);   // primary identifier for entity
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();           // prefix, suffix, encoding, frequency
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['tenant_id', 'identifier_type']);
            $table->index(['tenant_id', 'is_active']);
        });

        // ── Trace Logs (Immutable EPCIS-style traceability ledger) ─────────────
        Schema::create('trace_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            // The entity being traced (polymorphic)
            $table->string('entity_type', 100);     // batch, serial_number, product_variant
            $table->unsignedBigInteger('entity_id');

            $table->unsignedBigInteger('identifier_id')->nullable(); // AIDC identifier used in scan
            $table->enum('action_type', [
                'received',     // GRN / inbound
                'issued',       // delivery / outbound
                'transferred',  // location-to-location
                'returned',     // return from customer or to supplier
                'adjusted',     // inventory adjustment
                'disposed',     // scrap / write-off
                'scanned',      // ad-hoc scan (audit, lookup)
                'counted',      // cycle count
            ]);

            // Polymorphic document reference
            $table->string('reference_type', 100)->nullable();  // GoodsReceipt, DeliveryOrder, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->unsignedBigInteger('source_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();
            $table->decimal('quantity', 18, 4)->nullable();

            $table->unsignedBigInteger('user_id');
            $table->string('device_id', 100)->nullable();   // scanner / RFID reader device ID
            $table->json('metadata')->nullable();            // raw EPCIS event data, signal strength, etc.
            $table->timestamp('timestamp')->useCurrent();

            // NO updated_at — trace logs are immutable
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('identifier_id')->references('id')->on('identifiers')->nullOnDelete();
            $table->foreign('source_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('destination_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['tenant_id', 'action_type', 'timestamp']);
            $table->index(['entity_type', 'entity_id', 'timestamp']);   // forward traceability
            $table->index(['reference_type', 'reference_id']);
            $table->index(['identifier_id', 'timestamp']);
        });

        // ── Scan Sessions (WMS scan operations grouping) ───────────────────────
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('session_type', [
                'receiving',    // GRN scanning
                'picking',      // order picking
                'packing',      // packing confirmation
                'shipping',     // outbound shipment scan
                'counting',     // cycle count scanning
                'transfer',     // location transfer
            ]);
            // Document the session is tied to
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('user_id');
            $table->json('device_info')->nullable();     // device model, OS, scanner type
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['active', 'completed', 'aborted'])->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['tenant_id', 'session_type', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
        Schema::dropIfExists('trace_logs');
        Schema::dropIfExists('identifiers');
    }
};
