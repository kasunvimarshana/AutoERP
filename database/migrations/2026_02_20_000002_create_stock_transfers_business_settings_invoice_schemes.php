<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Business Settings ─────────────────────────────────────────────
        Schema::create('business_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('key', 255);
            $table->mediumText('value')->nullable();
            $table->string('group', 100)->default('general')
                ->comment('Logical grouping: general, pos, inventory, accounting, invoice, email');
            $table->boolean('is_public')->default(false)
                ->comment('Whether value can be read without authentication');
            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'uidx_tenant_setting_key');
            $table->index(['tenant_id', 'group'], 'idx_tenant_setting_group');
        });

        // ── Invoice Schemes ───────────────────────────────────────────────
        Schema::create('invoice_schemes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('name', 150);
            $table->string('scheme_type', 30)->default('purchase_n_sell')
                ->comment('purchase | sell | purchase_n_sell | expense | stock_adjustment');
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->unsignedInteger('start_number')->default(1);
            $table->unsignedInteger('number_of_digits')->default(4)
                ->comment('Zero-padded width for the numeric portion');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Stock Transfers ───────────────────────────────────────────────
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('reference_number', 60)->nullable();
            $table->string('from_warehouse_id');
            $table->string('to_warehouse_id');
            $table->string('status', 30)->default('draft')
                ->comment('draft | in_transit | received | cancelled');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('stock_transfer_id');
            $table->string('product_id');
            $table->string('variant_id')->nullable();
            $table->string('quantity', 30)->default('0.00000000');
            $table->string('cost_per_unit', 30)->default('0.00000000');
            $table->string('batch_number', 100)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('invoice_schemes');
        Schema::dropIfExists('business_settings');
    }
};
