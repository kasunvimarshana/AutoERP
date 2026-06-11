<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_base_uom_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('old_base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignId('new_base_uom_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->decimal('conversion_factor', 20, 6);
            $table->timestamp('effective_at');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('validation_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_base_uom_revisions_scope_idx');
            $table->index(['item_id', 'effective_at'], 'item_base_uom_revisions_item_effective_idx');
            $table->index('status', 'item_base_uom_revisions_status_idx');
        });

        foreach ([
            'inventory_movements',
            'inventory_reservations',
            'inventory_allocations',
            'inventory_valuation_layers',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('base_uom_id')
                    ->nullable()
                    ->after('item_id')
                    ->constrained('unit_of_measures')
                    ->nullOnDelete();
            });
        }

        DB::table('items')
            ->whereNotNull('base_uom_id')
            ->select(['id', 'base_uom_id'])
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    foreach ([
                        'inventory_movements',
                        'inventory_reservations',
                        'inventory_allocations',
                        'inventory_valuation_layers',
                    ] as $tableName) {
                        DB::table($tableName)
                            ->where('item_id', $item->id)
                            ->whereNull('base_uom_id')
                            ->update(['base_uom_id' => $item->base_uom_id]);
                    }
                }
            });
    }

    public function down(): void
    {
        foreach ([
            'inventory_valuation_layers',
            'inventory_allocations',
            'inventory_reservations',
            'inventory_movements',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('base_uom_id');
            });
        }

        Schema::dropIfExists('item_base_uom_revisions');
    }
};
