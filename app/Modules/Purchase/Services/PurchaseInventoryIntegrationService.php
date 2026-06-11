<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemBaseUomConversionService;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseInventoryIntegrationService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly ItemBaseUomConversionService $baseUomConversions,
    ) {}

    public function receipt(GoodsReceiptNote $grn, GoodsReceiptNoteLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $basis = $this->basis((int) $line->item_id, $line->uom_id ?? $line->base_uom_id, (string) $line->accepted_quantity, (string) $line->unit_price);

        return $this->movements->record(new StockMovementData(
            tenantId: (int) $grn->tenant_id,
            movementDate: $grn->received_date->toDateString(),
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $line->item_id,
            warehouseId: (int) $grn->warehouse_id,
            quantity: $basis['quantity'],
            organizationUnitId: $grn->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $grn->warehouse_location_id,
            unitCost: $basis['unit_cost'],
            sourceType: 'goods_receipt_note',
            sourceId: (int) $grn->getKey(),
            sourceLineType: 'goods_receipt_note_line',
            sourceLineId: (int) $line->getKey(),
            description: 'GRN '.$grn->grn_number,
        ), $postedBy);
    }

    public function returnOut(PurchaseReturn $return, PurchaseReturnLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $basis = $this->basis((int) $line->item_id, $line->uom_id, (string) $line->returned_quantity, (string) $line->unit_price);

        return $this->movements->record(new StockMovementData(
            tenantId: (int) $return->tenant_id,
            movementDate: $return->return_date->toDateString(),
            movementType: InventoryMovementType::ReturnOut,
            direction: InventoryDirection::Out,
            itemId: (int) $line->item_id,
            warehouseId: (int) $return->warehouse_id,
            quantity: $basis['quantity'],
            organizationUnitId: $return->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $return->warehouse_location_id,
            unitCost: $basis['unit_cost'],
            sourceType: 'purchase_return',
            sourceId: (int) $return->getKey(),
            sourceLineType: 'purchase_return_line',
            sourceLineId: (int) $line->getKey(),
            description: 'Purchase return '.$return->return_number,
        ), $postedBy);
    }

    public function reverseReceipt(GoodsReceiptNote $grn, GoodsReceiptNoteLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $basis = $this->basis((int) $line->item_id, $line->uom_id ?? $line->base_uom_id, (string) $line->accepted_quantity, (string) $line->unit_price);

        return $this->movements->record(new StockMovementData(
            tenantId: (int) $grn->tenant_id,
            movementDate: now()->toDateString(),
            movementType: InventoryMovementType::ReturnOut,
            direction: InventoryDirection::Out,
            itemId: (int) $line->item_id,
            warehouseId: (int) $grn->warehouse_id,
            quantity: $basis['quantity'],
            organizationUnitId: $grn->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $grn->warehouse_location_id,
            unitCost: $basis['unit_cost'],
            sourceType: 'goods_receipt_note_reversal',
            sourceId: (int) $grn->getKey(),
            sourceLineType: 'goods_receipt_note_line',
            sourceLineId: (int) $line->getKey(),
            description: 'GRN reversal '.$grn->grn_number,
        ), $postedBy);
    }

    private function affectsStock(int $itemId): bool
    {
        $item = Item::query()->findOrFail($itemId);

        return (bool) $item->is_stockable
            && ! in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true);
    }

    /**
     * @return array{quantity: string, unit_cost: string, factor: string}
     */
    private function basis(int $itemId, ?int $uomId, string $quantity, string $unitCost): array
    {
        $item = Item::query()->findOrFail($itemId);

        return $this->baseUomConversions->convertOperationalBasis(
            $item,
            $uomId ?? (int) $item->base_uom_id,
            $quantity,
            $unitCost,
        );
    }
}
