<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The baseline defines this key for new installations. Existing installs
        // require the additive index, retaining all historical debit notes.
        if (Schema::hasIndex('purchase_debit_notes', 'purchase_debit_notes_tenant_return_uk')) {
            return;
        }

        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->unique(
                ['tenant_id', 'purchase_return_id'],
                'purchase_debit_notes_tenant_return_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->dropUnique('purchase_debit_notes_tenant_return_uk');
        });
    }
};
