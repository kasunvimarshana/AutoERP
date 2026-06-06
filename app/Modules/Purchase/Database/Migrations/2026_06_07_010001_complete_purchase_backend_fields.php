<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->decimal('header_increase_total', 20, 6)->default('0.000000')->after('adjustment_total');
            $table->decimal('header_decrease_total', 20, 6)->default('0.000000')->after('header_increase_total');
            $table->timestamp('submitted_at')->nullable()->after('created_by');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->foreignId('ordered_uom_id')->nullable()->after('description')->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('base_uom_id')->nullable()->after('ordered_uom_id')->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000')->after('base_uom_id');
            $table->decimal('base_quantity', 20, 6)->default('0.000000')->after('ordered_quantity');
            $table->decimal('remaining_receivable_quantity', 20, 6)->default('0.000000')->after('remaining_quantity');
            $table->decimal('remaining_invoiceable_quantity', 20, 6)->default('0.000000')->after('remaining_receivable_quantity');
            $table->decimal('remaining_returnable_quantity', 20, 6)->default('0.000000')->after('remaining_invoiceable_quantity');
            $table->decimal('line_subtotal', 20, 6)->default('0.000000')->after('unit_price');
            $table->string('discount_calculation_type')->default('fixed')->after('line_subtotal');
            $table->decimal('discount_rate', 20, 6)->default('0.000000')->after('discount_calculation_type');
            $table->string('tax_calculation_type')->default('fixed')->after('discount_amount');
            $table->decimal('tax_rate', 20, 6)->default('0.000000')->after('tax_calculation_type');
            $table->string('charge_calculation_type')->default('fixed')->after('tax_amount');
            $table->decimal('charge_rate', 20, 6)->default('0.000000')->after('charge_calculation_type');
        });

        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->foreignId('ordered_uom_id')->nullable()->after('description')->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('base_uom_id')->nullable()->after('ordered_uom_id')->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('uom_conversion_factor', 20, 6)->default('1.000000')->after('base_uom_id');
            $table->decimal('base_received_quantity', 20, 6)->default('0.000000')->after('received_quantity');
            $table->decimal('base_accepted_quantity', 20, 6)->default('0.000000')->after('accepted_quantity');
            $table->decimal('line_subtotal', 20, 6)->default('0.000000')->after('unit_price');
        });

        Schema::table('purchase_header_adjustments', function (Blueprint $table): void {
            $table->string('calculation_base')->default('subtotal')->after('calculation_type');
        });

        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->string('return_type')->default('referenced')->after('return_number');
            $table->string('source_type')->nullable()->after('return_type');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->boolean('approval_required')->default(false)->after('reason');
            $table->boolean('affects_supplier_balance')->default(true)->after('approval_required');
            $table->decimal('cost_basis', 20, 6)->nullable()->after('affects_supplier_balance');
            $table->json('audit_metadata')->nullable()->after('cost_basis');
        });

        Schema::table('purchase_return_lines', function (Blueprint $table): void {
            $table->decimal('cost_basis', 20, 6)->nullable()->after('unit_price');
        });

        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->string('source_type')->nullable()->after('purchase_return_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->unsignedBigInteger('approved_by')->nullable()->after('reason');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->dropColumn(['source_type', 'source_id', 'approved_by', 'approved_at']);
        });

        Schema::table('purchase_return_lines', function (Blueprint $table): void {
            $table->dropColumn('cost_basis');
        });

        Schema::table('purchase_returns', function (Blueprint $table): void {
            $table->dropColumn(['return_type', 'source_type', 'source_id', 'approval_required', 'affects_supplier_balance', 'cost_basis', 'audit_metadata']);
        });

        Schema::table('purchase_header_adjustments', function (Blueprint $table): void {
            $table->dropColumn('calculation_base');
        });

        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ordered_uom_id');
            $table->dropConstrainedForeignId('base_uom_id');
            $table->dropColumn(['uom_conversion_factor', 'base_received_quantity', 'base_accepted_quantity', 'line_subtotal']);
        });

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ordered_uom_id');
            $table->dropConstrainedForeignId('base_uom_id');
            $table->dropColumn([
                'uom_conversion_factor',
                'base_quantity',
                'remaining_receivable_quantity',
                'remaining_invoiceable_quantity',
                'remaining_returnable_quantity',
                'line_subtotal',
                'discount_calculation_type',
                'discount_rate',
                'tax_calculation_type',
                'tax_rate',
                'charge_calculation_type',
                'charge_rate',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn(['header_increase_total', 'header_decrease_total', 'submitted_at', 'submitted_by']);
        });
    }
};
