<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->foreignId('default_tax_group_id')->nullable()->after('base_uom_id')->constrained('tax_groups', 'id')->nullOnDelete();
            $table->foreignId('purchase_tax_group_id')->nullable()->after('default_tax_group_id')->constrained('tax_groups', 'id')->nullOnDelete();
            $table->foreignId('sales_tax_group_id')->nullable()->after('purchase_tax_group_id')->constrained('tax_groups', 'id')->nullOnDelete();
            $table->boolean('is_tax_exempt')->default(false)->after('is_combo');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropForeign(['default_tax_group_id']);
            $table->dropForeign(['purchase_tax_group_id']);
            $table->dropForeign(['sales_tax_group_id']);
            $table->dropColumn([
                'default_tax_group_id',
                'purchase_tax_group_id',
                'sales_tax_group_id',
                'is_tax_exempt',
            ]);
        });
    }
};
