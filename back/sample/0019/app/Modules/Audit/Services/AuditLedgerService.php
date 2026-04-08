<?php

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Models\InventoryAuditLedger;
use App\Modules\Audit\Models\AuditTrail;
use App\Modules\StockMovement\Models\StockMovement;
use App\Modules\StockMovement\Models\StockMovementLine;
use App\Modules\Returns\Models\ReturnMerchandiseAuthorization;
use App\Modules\Returns\Models\SupplierReturnLine;
use App\Modules\CycleCounting\Models\CycleCountItem;
use App\Modules\StockMovement\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditLedgerService
 *
 * Writes immutable audit entries to the inventory_audit_ledger table.
 * These records are append-only and should never be updated or deleted.
 *
 * Provides a full double-entry stock ledger for:
 *   - Receipts, Deliveries, Transfers
 *   - Returns (customer and supplier)
 *   - Adjustments and Scrap
 *   - Cycle Count adjustments
 *   - Revaluations
 *
 * Also writes to audit_trails for model-level change tracking.
 */
class AuditLedgerService
{
    /**
     * Write an audit ledger entry for a completed stock movement line.
     */
    public function writeMovement(
        StockMovementLine $line,
        StockMovement     $movement,
        array             $cogsInfo,
        $effectiveDate
    ): InventoryAuditLedger {
        $category    = $movement->operationType->category;
        $txType      = $this->movementCategoryToTxType($category, $movement);

        // Determine warehouse from location
        $warehouseId = $movement->warehouse_id
            ?? $this->resolveWarehouseFromLocation($line->destination_location_id ?? $line->source_location_id);

        return InventoryAuditLedger::create([
            'tenant_id'        => $movement->tenant_id,
            'organization_id'  => $movement->organization_id,
            'warehouse_id'     => $warehouseId,
            'location_id'      => $line->destination_location_id ?? $line->source_location_id,
            'product_id'       => $line->product_id,
            'variant_id'       => $line->variant_id,
            'lot_id'           => $line->lot_id,
            'uom_id'           => $line->uom_id,
            'transaction_type' => $txType,
            'from_location_id' => $line->source_location_id,
            'to_location_id'   => $line->destination_location_id,
            'qty'              => $line->done_qty,
            'qty_before'       => 0, // Populated by DB trigger or separate call
            'qty_after'        => 0,
            'unit_cost'        => $cogsInfo['unit_cost'],
            'total_cost'       => $cogsInfo['total_cogs'],
            'value_before'     => 0,
            'value_after'      => 0,
            'costing_method'   => $cogsInfo['method'] ?? null,
            'reference_type'   => StockMovementLine::class,
            'reference_id'     => $line->id,
            'reference_number' => $movement->reference_number,
            'created_by'       => Auth::id(),
            'created_by_name'  => Auth::user()?->name,
            'transaction_date' => $effectiveDate,
        ]);
    }

    /**
     * Write audit entry for a customer return receipt.
     */
    public function writeReturnReceipt(ReturnMerchandiseAuthorization $rma, $movement): void
    {
        foreach ($rma->lines as $line) {
            if ($line->received_qty <= 0) continue;

            InventoryAuditLedger::create([
                'tenant_id'        => $rma->tenant_id,
                'warehouse_id'     => $rma->warehouse_id,
                'product_id'       => $line->product_id,
                'variant_id'       => $line->variant_id,
                'lot_id'           => $line->restocked_lot_id ?? $line->original_lot_id,
                'uom_id'           => $line->uom_id,
                'transaction_type' => 'return',
                'to_location_id'   => $rma->return_location_id,
                'qty'              => $line->received_qty,
                'qty_before'       => 0,
                'qty_after'        => $line->received_qty,
                'unit_cost'        => $line->original_unit_cost ?? 0,
                'total_cost'       => ($line->original_unit_cost ?? 0) * $line->received_qty,
                'reference_type'   => ReturnMerchandiseAuthorization::class,
                'reference_id'     => $rma->id,
                'reference_number' => $rma->rma_number,
                'partner_id'       => $rma->customer_id,
                'partner_type'     => 'customer',
                'created_by'       => Auth::id(),
                'created_by_name'  => Auth::user()?->name,
                'transaction_date' => now(),
            ]);
        }
    }

    /**
     * Write audit entry for a supplier return (RTV).
     */
    public function writeSupplierReturn(SupplierReturnLine $line, $rtv): void
    {
        InventoryAuditLedger::create([
            'tenant_id'        => $rtv->tenant_id,
            'warehouse_id'     => $rtv->warehouse_id,
            'product_id'       => $line->product_id,
            'variant_id'       => $line->variant_id,
            'lot_id'           => $line->lot_id,
            'uom_id'           => $line->uom_id,
            'transaction_type' => 'transfer_out',
            'from_location_id' => $line->source_location_id,
            'qty'              => $line->return_qty,
            'qty_before'       => 0,
            'qty_after'        => 0,
            'unit_cost'        => $line->original_unit_cost ?? 0,
            'total_cost'       => ($line->original_unit_cost ?? 0) * $line->return_qty,
            'reference_type'   => SupplierReturnLine::class,
            'reference_id'     => $line->id,
            'reference_number' => $rtv->rtv_number,
            'partner_id'       => $rtv->supplier_id,
            'partner_type'     => 'supplier',
            'created_by'       => Auth::id(),
            'created_by_name'  => Auth::user()?->name,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Write audit entry for a cycle count adjustment.
     */
    public function writeCycleCountAdjustment(CycleCountItem $item, StockAdjustment $adjustment): void
    {
        InventoryAuditLedger::create([
            'tenant_id'        => $item->session->tenant_id,
            'warehouse_id'     => $item->warehouse_id,
            'location_id'      => $item->location_id,
            'product_id'       => $item->product_id,
            'variant_id'       => $item->variant_id,
            'lot_id'           => $item->lot_id,
            'uom_id'           => $item->uom_id,
            'transaction_type' => 'cycle_count_adj',
            'qty'              => abs($item->variance_qty),
            'qty_before'       => $item->system_qty,
            'qty_after'        => $item->reconciled_qty,
            'unit_cost'        => $item->system_unit_cost,
            'total_cost'       => abs($item->variance_value),
            'value_before'     => $item->system_qty * $item->system_unit_cost,
            'value_after'      => $item->reconciled_qty * $item->system_unit_cost,
            'reference_type'   => StockAdjustment::class,
            'reference_id'     => $adjustment->id,
            'reference_number' => $adjustment->reference_number,
            'created_by'       => Auth::id(),
            'created_by_name'  => Auth::user()?->name,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Write a general audit trail entry for any model event.
     * Called by model Observers.
     */
    public function writeModelEvent(
        string $event,
        string $modelClass,
        int    $modelId,
        array  $oldValues   = [],
        array  $newValues   = [],
        array  $context     = []
    ): AuditTrail {
        return AuditTrail::create([
            'tenant_id'              => $context['tenant_id'] ?? null,
            'organization_id'        => $context['organization_id'] ?? null,
            'user_id'                => Auth::id(),
            'user_name'              => Auth::user()?->name,
            'user_email'             => Auth::user()?->email,
            'performed_by_type'      => 'user',
            'ip_address'             => Request::ip(),
            'user_agent'             => Request::userAgent(),
            'session_id'             => session()->getId(),
            'event'                  => $event,
            'auditable_type'         => $modelClass,
            'auditable_id'           => $modelId,
            'auditable_ref'          => $context['ref'] ?? null,
            'old_values'             => $oldValues ?: null,
            'new_values'             => $newValues ?: null,
            'changed_fields'         => array_keys(array_diff_assoc($newValues, $oldValues)) ?: null,
            'action_description'     => $context['description'] ?? null,
            'module'                 => $context['module'] ?? null,
            'url'                    => Request::fullUrl(),
            'http_method'            => Request::method(),
            'route_name'             => Request::route()?->getName(),
            'severity'               => $context['severity'] ?? 'info',
            'is_compliance_relevant' => $context['compliance'] ?? false,
            'occurred_at'            => now(),
        ]);
    }

    protected function movementCategoryToTxType(string $category, StockMovement $movement): string
    {
        return match ($category) {
            'incoming'       => 'receipt',
            'outgoing'       => 'delivery',
            'returns'        => 'return',
            'manufacturing'  => str_contains($movement->reference_number ?? '', 'OUT') ? 'production_out' : 'production_in',
            'scrap'          => 'scrap',
            default          => 'transfer_in',
        };
    }

    protected function resolveWarehouseFromLocation(?int $locationId): ?int
    {
        if (! $locationId) return null;
        return \App\Modules\Warehouse\Models\WarehouseLocation::find($locationId)?->warehouse_id;
    }
}
