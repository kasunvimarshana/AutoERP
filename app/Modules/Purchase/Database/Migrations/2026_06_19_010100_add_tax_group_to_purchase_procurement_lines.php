<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->foreignId('tax_group_id')
                ->nullable()
                ->after('tax_amount')
                ->constrained('tax_groups')
                ->nullOnDelete();
            $table->index('tax_group_id', 'purchase_order_lines_tax_group_idx');
        });

        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->foreignId('tax_group_id')
                ->nullable()
                ->after('tax_amount')
                ->constrained('tax_groups')
                ->nullOnDelete();
            $table->index('tax_group_id', 'goods_receipt_note_lines_tax_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_note_lines', function (Blueprint $table): void {
            $table->dropForeign(['tax_group_id']);
            $table->dropIndex('goods_receipt_note_lines_tax_group_idx');
            $table->dropColumn('tax_group_id');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->dropForeign(['tax_group_id']);
            $table->dropIndex('purchase_order_lines_tax_group_idx');
            $table->dropColumn('tax_group_id');
        });
    }
};
