<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A purchase return has one linked debit note. Nullable purchase_return_id
        // permits independent manual notes without manufacturing a source.
        // Existing duplicates must be investigated, never silently discarded.
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
