<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Services\Inventory\AlertService;
use App\Services\Inventory\InventoryEngine;
use App\Models\{StockPosition, InventorySnapshot, InventorySnapshotLine, ProductClassification};
use Illuminate\Support\Facades\DB;

// ═════════════════════════════════════════════════════════════════════════════
// Command: inventory:scan-expiry
// Schedule: daily
// ═════════════════════════════════════════════════════════════════════════════
class ScanExpiryCommand extends Command
{
    protected $signature   = 'inventory:scan-expiry {--org= : Limit to specific organization}';
    protected $description = 'Scan lots and batches for expiring/expired items and create alerts';

    public function handle(AlertService $alerts): int
    {
        $orgs = $this->option('org')
            ? Organization::where('id', $this->option('org'))->get()
            : Organization::where('is_active', true)->get();

        foreach ($orgs as $org) {
            $this->line("Scanning expiry for: {$org->name}");
            $alerts->scanExpiry($org->id);
        }

        $this->info('Expiry scan complete.');
        return Command::SUCCESS;
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// Command: inventory:process-reorders
// Schedule: hourly
// ═════════════════════════════════════════════════════════════════════════════
class ProcessReordersCommand extends Command
{
    protected $signature   = 'inventory:process-reorders {--org= : Limit to specific organization}';
    protected $description = 'Evaluate reorder rules and trigger alerts or auto-POs';

    public function handle(AlertService $alerts): int
    {
        $orgs = $this->option('org')
            ? Organization::where('id', $this->option('org'))->get()
            : Organization::where('is_active', true)->get();

        foreach ($orgs as $org) {
            $triggered = $alerts->processReorderRules($org->id);
            $this->line("  {$org->name}: {$triggered} rules triggered");
        }

        $this->info('Reorder processing complete.');
        return Command::SUCCESS;
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// Command: inventory:snapshot
// Schedule: first of month at 00:00
// ═════════════════════════════════════════════════════════════════════════════
class GenerateSnapshotCommand extends Command
{
    protected $signature   = 'inventory:snapshot {--org= : Organization ID} {--type=monthly : daily|monthly|custom}';
    protected $description = 'Generate an inventory valuation snapshot for reporting and period-end';

    public function handle(): int
    {
        $orgs = $this->option('org')
            ? Organization::where('id', $this->option('org'))->get()
            : Organization::where('is_active', true)->get();

        $type   = $this->option('type');
        $period = $type === 'daily' ? now()->format('Y-m-d') : now()->format('Y-m');

        foreach ($orgs as $org) {
            $this->generateForOrg($org, $type, $period);
            $this->line("Snapshot generated for {$org->name} [{$period}]");
        }

        $this->info('Snapshots complete.');
        return Command::SUCCESS;
    }

    private function generateForOrg(Organization $org, string $type, string $period): void
    {
        DB::transaction(function () use ($org, $type, $period) {
            // Aggregate positions per product/variant/warehouse
            $positions = StockPosition::where('organization_id', $org->id)
                ->selectRaw('product_id, product_variant_id, warehouse_id, lot_id,
                             SUM(qty_on_hand) as total_qty,
                             AVG(average_cost) as avg_cost,
                             SUM(total_cost_value) as total_value')
                ->groupBy('product_id', 'product_variant_id', 'warehouse_id', 'lot_id')
                ->get();

            $snapshot = InventorySnapshot::create([
                'organization_id'   => $org->id,
                'period'            => $period,
                'snapshot_type'     => $type,
                'valuation_method'  => \App\Models\InventorySettings::where('organization_id', $org->id)->value('default_valuation_method') ?? 'AVCO',
                'total_items'       => $positions->count(),
                'total_quantity'    => $positions->sum('total_qty'),
                'total_cost_value'  => $positions->sum('total_value'),
                'total_retail_value'=> $this->calcRetailValue($positions),
                'generated_at'      => now(),
                'generated_by'      => 1, // system
            ]);

            foreach ($positions as $pos) {
                InventorySnapshotLine::create([
                    'inventory_snapshot_id' => $snapshot->id,
                    'product_id'            => $pos->product_id,
                    'product_variant_id'    => $pos->product_variant_id,
                    'warehouse_id'          => $pos->warehouse_id,
                    'lot_id'                => $pos->lot_id,
                    'quantity'              => $pos->total_qty,
                    'average_cost'          => $pos->avg_cost,
                    'total_cost_value'      => $pos->total_value,
                ]);
            }
        });
    }

    private function calcRetailValue($positions): float
    {
        return $positions->sum(function ($pos) {
            $price = \App\Models\Product::find($pos->product_id)?->standard_price ?? 0;
            return $pos->total_qty * $price;
        });
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// Command: inventory:classify-abc
// Schedule: monthly
// ═════════════════════════════════════════════════════════════════════════════
class ClassifyAbcCommand extends Command
{
    protected $signature   = 'inventory:classify-abc {--org= : Organization ID}';
    protected $description = 'Run ABC/XYZ classification analysis on all products';

    public function handle(): int
    {
        $orgs = $this->option('org')
            ? Organization::where('id', $this->option('org'))->get()
            : Organization::where('is_active', true)->get();

        foreach ($orgs as $org) {
            $this->classifyOrg($org);
            $this->line("ABC classification complete for {$org->name}");
        }

        return Command::SUCCESS;
    }

    private function classifyOrg(Organization $org): void
    {
        $period    = now()->format('Y');
        $startDate = now()->startOfYear();

        // Get annual demand value per product
        $products = \App\Models\StockLedgerEntry::where('organization_id', $org->id)
            ->where('direction', 'OUT')
            ->where('movement_date', '>=', $startDate)
            ->whereIn('movement_type', ['sales_issue', 'production_consume'])
            ->selectRaw('product_id, warehouse_id,
                         SUM(quantity) as annual_qty,
                         SUM(total_cost) as annual_value,
                         STDDEV(quantity) / NULLIF(AVG(quantity), 0) as cv')
            ->groupBy('product_id', 'warehouse_id')
            ->orderByDesc('annual_value')
            ->get();

        $totalValue    = $products->sum('annual_value');
        $cumulativeValue = 0;

        foreach ($products as $i => $product) {
            $cumulativeValue += $product->annual_value;
            $cumulativePct    = $totalValue > 0 ? ($cumulativeValue / $totalValue) * 100 : 0;

            // ABC: A = top 80% of value, B = next 15%, C = bottom 5%
            $abcClass = match(true) {
                $cumulativePct <= 80 => 'A',
                $cumulativePct <= 95 => 'B',
                default              => 'C',
            };

            // XYZ: X = CV < 0.5 (stable), Y = 0.5-1.0 (variable), Z = > 1.0 (irregular)
            $cv = $product->cv ?? 0;
            $xyzClass = match(true) {
                $cv < 0.5  => 'X',
                $cv < 1.0  => 'Y',
                default    => 'Z',
            };

            // Velocity: fast > 100/yr, medium > 20, slow > 0, dead = 0
            $velocityClass = match(true) {
                $product->annual_qty > 100 => 'fast',
                $product->annual_qty > 20  => 'medium',
                $product->annual_qty > 0   => 'slow',
                default                    => 'dead',
            };

            ProductClassification::updateOrCreate(
                ['product_id' => $product->product_id, 'warehouse_id' => $product->warehouse_id, 'period' => $period],
                [
                    'organization_id'    => $org->id,
                    'abc_class'          => $abcClass,
                    'xyz_class'          => $xyzClass,
                    'velocity_class'     => $velocityClass,
                    'annual_demand_value'=> $product->annual_value,
                    'annual_demand_qty'  => $product->annual_qty,
                    'demand_variability' => round($cv, 4),
                ]
            );
        }
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// Command: inventory:expire-soft-reservations
// Schedule: every 15 minutes
// ═════════════════════════════════════════════════════════════════════════════
class ExpireSoftReservationsCommand extends Command
{
    protected $signature   = 'inventory:expire-reservations';
    protected $description = 'Release expired soft reservations back to available stock';

    public function handle(): int
    {
        $expired = \App\Models\StockAllocation::where('allocation_type', 'soft')
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $allocation) {
            DB::transaction(function () use ($allocation) {
                // Return qty to available pool
                StockPosition::where('product_id', $allocation->product_id)
                    ->where('warehouse_id', $allocation->warehouse_id)
                    ->update([
                        'qty_available' => DB::raw("qty_available + {$allocation->quantity_allocated}"),
                        'qty_reserved'  => DB::raw("GREATEST(qty_reserved - {$allocation->quantity_allocated}, 0)"),
                    ]);

                $allocation->update(['status' => 'expired']);
            });
        }

        if ($expired->count() > 0) {
            $this->line("Released {$expired->count()} expired soft reservations.");
        }

        return Command::SUCCESS;
    }
}
