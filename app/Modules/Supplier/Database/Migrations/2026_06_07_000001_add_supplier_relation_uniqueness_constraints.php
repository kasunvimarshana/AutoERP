<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_bank_accounts', function (Blueprint $table): void {
            $table->unique(['supplier_id', 'account_number'], 'supplier_bank_accounts_supplier_number_uk');
        });

        Schema::table('supplier_item_mappings', function (Blueprint $table): void {
            $table->unique(
                ['supplier_id', 'item_id', 'item_variant_id'],
                'supplier_item_mappings_supplier_item_variant_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::table('supplier_item_mappings', function (Blueprint $table): void {
            $table->dropUnique('supplier_item_mappings_supplier_item_variant_uk');
        });

        Schema::table('supplier_bank_accounts', function (Blueprint $table): void {
            $table->dropUnique('supplier_bank_accounts_supplier_number_uk');
        });
    }
};
