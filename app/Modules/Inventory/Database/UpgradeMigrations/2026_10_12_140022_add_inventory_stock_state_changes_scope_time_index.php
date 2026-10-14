<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('inventory_stock_state_changes', 'inventory_stock_state_changes_scope_time_ix')) {
            return;
        }

        Schema::table('inventory_stock_state_changes', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'organization_unit_id', 'occurred_at'],
                'inventory_stock_state_changes_scope_time_ix',
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_state_changes', function (Blueprint $table): void {
            $table->dropIndex('inventory_stock_state_changes_scope_time_ix');
        });
    }
};
