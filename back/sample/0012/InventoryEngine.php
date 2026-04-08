<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\ValueObjects\AllocationAlgorithm;
use Modules\Inventory\Domain\ValueObjects\MovementType;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

/**
 * InventoryEngine
 *
 * Core orchestrator for all stock movements.
 * Resolves valuation method, rotation strategy, and allocation algorithm
 * per-tenant → per-organization → per-warehouse → per-product hierarchy.
 *
 * Usage:
 *   app(InventoryEngine::class)->receiveStock([...])
 *   app(InventoryEngine::class)->issueStock([...])
 *   app(InventoryEngine::class)->transferStock([...])
 *   app(InventoryEngine::class)->adjustStock([...])
 */
final class InventoryEngine
{
    public function __construct(
        private readonly ValuationService  $valuationService,
        private readonly RotationService   $rotationService,
        private readonly AllocationService $allocationService,
        private readonly LedgerService     $ledgerService,
        private readonly SettingsResolver  $settingsResolver,
    ) {}

    // ── Stock IN ─────────────────────────────────────────────────────────────
    public function receiveStock(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $this->validate($params, ['tenant_id','product_id','warehouse_id','quantity','unit_cost','movement_type']);

            $method = $this->settingsResolver->resolveValuationMethod(
                tenantId:   $params['tenant_id'],
                productId:  $params['product_id'],
                warehouseId: $params['warehouse_id'],
                override:   $params['valuation_method'] ?? null,
            );

            // 1. Update position
            $position = $this->upsertPosition($params, 'IN');

            // 2. Append immutable ledger entry
            $entry = $this->ledgerService->write($params, $position, $method, 'IN');

            // 3. Create costing layer (for layer-based methods)
            if ((new ValuationMethod($method))->isLayerBased()) {
                $this->valuationService->createLayer($entry, $params, $method);
            }

            // 4. Recalculate AVCO
            if ($method === ValuationMethod::AVCO) {
                $this->valuationService->recalculateAvco(
                    $params['product_id'],
                    $params['warehouse_id'],
                    $params['product_variant_id'] ?? null,
                    $params['quantity'],
                    $params['unit_cost'],
                );
            }

            // 5. Lot/batch quantity update
            if (!empty($params['lot_id'])) {
                $this->incrementLotQty($params['lot_id'], $params['quantity']);
            }

            // 6. Trigger alerts
            $this->checkAlerts($params['tenant_id'], $params['product_id'], $params['warehouse_id']);

            return ['entry' => $entry, 'position' => $position];
        });
    }

    // ── Stock OUT ────────────────────────────────────────────────────────────
    public function issueStock(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $this->validate($params, ['tenant_id','product_id','warehouse_id','quantity','movement_type']);

            $method = $this->settingsResolver->resolveValuationMethod(
                tenantId:   $params['tenant_id'],
                productId:  $params['product_id'],
                warehouseId: $params['warehouse_id'],
                override:   $params['valuation_method'] ?? null,
            );

            $rotation = $this->settingsResolver->resolveRotationStrategy(
                tenantId:   $params['tenant_id'],
                productId:  $params['product_id'],
                warehouseId: $params['warehouse_id'],
                override:   $params['rotation_strategy'] ?? null,
            );

            // Guard against negative stock
            $settings = $this->settingsResolver->getTenantSettings($params['tenant_id']);
            $available = $this->getAvailableQty(
                $params['product_id'], $params['warehouse_id'],
                $params['product_variant_id'] ?? null
            );

            if (!$settings['allow_negative_stock'] && $available < $params['quantity']) {
                throw new \RuntimeException(
                    "Insufficient stock. Available: {$available}, Requested: {$params['quantity']}"
                );
            }

            // Resolve unit cost from valuation layers
            $costResult = $this->valuationService->resolveIssueCost(
                productId:   $params['product_id'],
                variantId:   $params['product_variant_id'] ?? null,
                warehouseId: $params['warehouse_id'],
                quantity:    $params['quantity'],
                method:      $method,
                lotId:       $params['lot_id'] ?? null,
                batchId:     $params['batch_id'] ?? null,
            );

            $params['unit_cost']       = $costResult['unit_cost'];
            $params['total_cost']      = $costResult['total_cost'];
            $params['consumed_layers'] = $costResult['consumed_layers'] ?? [];

            $position = $this->upsertPosition($params, 'OUT');
            $entry    = $this->ledgerService->write($params, $position, $method, 'OUT');

            // Consume costing layers and record trail
            foreach ($params['consumed_layers'] as $c) {
                DB::table('costing_layer_consumptions')->insert([
                    'costing_layer_id'  => $c['layer_id'],
                    'ledger_entry_id'   => $entry['id'],
                    'quantity_consumed' => $c['quantity'],
                    'unit_cost'         => $c['unit_cost'],
                    'total_cost'        => $c['total_cost'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                DB::table('costing_layers')
                    ->where('id', $c['layer_id'])
                    ->decrement('remaining_quantity', $c['quantity']);
                DB::table('costing_layers')
                    ->where('id', $c['layer_id'])
                    ->where('remaining_quantity', '<=', 0)
                    ->update(['is_fully_consumed' => true, 'fully_consumed_at' => now()]);
            }

            if ($method === ValuationMethod::AVCO) {
                $this->valuationService->recalculateAvco(
                    $params['product_id'], $params['warehouse_id'],
                    $params['product_variant_id'] ?? null,
                    -$params['quantity'], $costResult['unit_cost'],
                );
            }

            if (!empty($params['lot_id'])) {
                $this->decrementLotQty($params['lot_id'], $params['quantity']);
            }
            if (!empty($params['serial_number_id'])) {
                DB::table('serial_numbers')
                    ->where('id', $params['serial_number_id'])
                    ->update(['status' => 'sold', 'sold_at' => now()]);
            }

            $this->checkAlerts($params['tenant_id'], $params['product_id'], $params['warehouse_id']);

            return ['entry' => $entry, 'position' => $position];
        });
    }

    // ── Transfer ─────────────────────────────────────────────────────────────
    public function transferStock(array $params): array
    {
        return DB::transaction(function () use ($params) {
            $out = $this->issueStock(array_merge($params, [
                'warehouse_id'  => $params['source_warehouse_id'],
                'movement_type' => MovementType::TRANSFER_OUT,
            ]));

            $in = $this->receiveStock(array_merge($params, [
                'warehouse_id'  => $params['destination_warehouse_id'],
                'movement_type' => MovementType::TRANSFER_IN,
                'unit_cost'     => $out['entry']['unit_cost'],
            ]));

            return ['out' => $out, 'in' => $in];
        });
    }

    // ── Adjust ───────────────────────────────────────────────────────────────
    public function adjustStock(array $params): array
    {
        $qty = $params['quantity_adjusted'] ?? 0;
        $dir = $qty >= 0 ? 'IN' : 'OUT';
        $params['quantity']      = abs($qty);
        $params['movement_type'] = $dir === 'IN'
            ? MovementType::ADJUSTMENT_POSITIVE
            : MovementType::ADJUSTMENT_NEGATIVE;
        $params['unit_cost'] = $params['unit_cost']
            ?? $this->getCurrentAvgCost($params['product_id'], $params['warehouse_id']);

        return $dir === 'IN'
            ? $this->receiveStock($params)
            : $this->issueStock($params);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function upsertPosition(array $params, string $direction): array
    {
        $key = [
            'product_id'         => $params['product_id'],
            'product_variant_id' => $params['product_variant_id'] ?? null,
            'warehouse_id'       => $params['warehouse_id'],
            'storage_location_id'=> $params['storage_location_id'] ?? null,
            'lot_id'             => $params['lot_id'] ?? null,
            'batch_id'           => $params['batch_id'] ?? null,
        ];

        $existing = DB::table('stock_positions')
            ->where($key)
            ->lockForUpdate()
            ->first();

        $qty = $params['quantity'];

        if ($existing) {
            $onHand    = $existing->qty_on_hand + ($direction === 'IN' ? $qty : -$qty);
            $available = $existing->qty_available + ($direction === 'IN' ? $qty : -$qty);
            DB::table('stock_positions')->where('id', $existing->id)->update([
                'qty_on_hand'      => $onHand,
                'qty_available'    => $available,
                'last_movement_at' => now(),
                'updated_at'       => now(),
            ]);
            return array_merge((array) $existing, ['qty_on_hand' => $onHand, 'qty_available' => $available]);
        }

        $onHand = $direction === 'IN' ? $qty : -$qty;
        $id = DB::table('stock_positions')->insertGetId(array_merge($key, [
            'tenant_id'        => $params['tenant_id'],
            'uom_id'           => $params['uom_id'] ?? null,
            'qty_on_hand'      => $onHand,
            'qty_available'    => $onHand,
            'average_cost'     => $params['unit_cost'] ?? 0,
            'total_cost_value' => $onHand * ($params['unit_cost'] ?? 0),
            'last_movement_at' => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]));
        return array_merge($key, ['id' => $id, 'qty_on_hand' => $onHand]);
    }

    private function getAvailableQty(int $productId, int $warehouseId, ?int $variantId): float
    {
        return (float) DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
            ->sum('qty_available');
    }

    private function getCurrentAvgCost(int $productId, int $warehouseId): float
    {
        return (float) DB::table('stock_positions')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->avg('average_cost') ?? 0;
    }

    private function incrementLotQty(int $lotId, float $qty): void
    {
        DB::table('lots')->where('id', $lotId)->increment('available_quantity', $qty);
    }

    private function decrementLotQty(int $lotId, float $qty): void
    {
        DB::table('lots')->where('id', $lotId)->decrement('available_quantity', $qty);
    }

    private function checkAlerts(int $tenantId, int $productId, int $warehouseId): void
    {
        // Delegate to AlertService (injected at service provider level)
        app(AlertService::class)->evaluate($tenantId, $productId, $warehouseId);
    }

    private function validate(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: {$field}");
            }
        }
    }
}
