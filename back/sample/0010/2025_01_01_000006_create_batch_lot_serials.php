<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Batches ──────────────────────────────────────────────────────────
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('batch_number')->index();
            $table->string('external_batch_ref')->nullable();
            $table->string('status')->default('active');
            // active|quarantine|hold|rejected|consumed|expired|recalled

            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->date('received_date')->nullable();

            $table->string('supplier_name')->nullable();
            $table->string('country_of_origin', 2)->nullable();
            $table->string('certificate_number')->nullable();

            $table->string('qc_status')->default('pending');
            // pending|passed|failed|waived
            $table->text('qc_notes')->nullable();
            $table->timestamp('qc_tested_at')->nullable();
            $table->unsignedBigInteger('qc_tested_by')->nullable();

            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->json('landed_costs')->nullable();

            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'batch_number'], 'batch_number_unique');
            $table->index(['product_id', 'status']);
            $table->index('expiry_date');
        });

        // ── Lots ─────────────────────────────────────────────────────────────
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('storage_location_id')->nullable()->index();
            $table->string('lot_number')->index();
            $table->string('status')->default('available');
            // available|reserved|quarantine|hold|rejected|consumed|expired|damaged|returned

            // ── Quantity buckets ───────────────────────────────────────────
            $table->decimal('initial_quantity', 20, 4)->default(0);
            $table->decimal('available_quantity', 20, 4)->default(0);
            $table->decimal('reserved_quantity', 20, 4)->default(0);
            $table->decimal('damaged_quantity', 20, 4)->default(0);
            $table->decimal('quarantine_quantity', 20, 4)->default(0);
            $table->unsignedBigInteger('uom_id')->nullable();

            // ── Valuation snapped at receipt ───────────────────────────────
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('valuation_method', 30)->nullable();

            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'lot_number'], 'lot_number_unique');
            $table->index(['product_id', 'warehouse_id', 'status']);
            $table->index('expiry_date');
        });

        // ── Serial Numbers ────────────────────────────────────────────────────
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('storage_location_id')->nullable()->index();
            $table->string('serial_number')->index();
            $table->string('status')->default('in_stock');
            // in_stock|reserved|sold|returned|defective|scrapped|in_transit|quarantine|on_loan

            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('selling_price', 20, 4)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->date('received_date')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->unsignedBigInteger('sold_to_customer_id')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('imei')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'serial_number'], 'serial_number_unique');
            $table->index(['product_id', 'status']);
        });

        // ── Batch Genealogy (full traceability tree) ──────────────────────────
        Schema::create('batch_genealogy', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_batch_id')->nullable()->index();
            $table->unsignedBigInteger('child_batch_id')->nullable()->index();
            $table->unsignedBigInteger('parent_lot_id')->nullable()->index();
            $table->unsignedBigInteger('child_lot_id')->nullable()->index();
            $table->string('relationship_type');
            // split|merge|transform|consume|produce|rework
            $table->decimal('quantity', 20, 4)->nullable();
            $table->timestamp('occurred_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── Batch Documents (CoA, MSDS, inspection reports) ───────────────────
        Schema::create('batch_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->string('document_type');
            // coa|msds|inspection|customs|recall|certificate|other
            $table->string('title');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_documents');
        Schema::dropIfExists('batch_genealogy');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('lots');
        Schema::dropIfExists('batches');
    }
};
