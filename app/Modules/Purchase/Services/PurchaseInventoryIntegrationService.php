<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseInventoryIntegrationService
{
    public function __construct(
        private readonly InventoryFacade $inventory,
        private readonly DecimalMath $math,
        private readonly PurchaseAcquisitionCostAllocator $costs,
    ) {}

    public function receipt(GoodsReceiptNote $grn, GoodsReceiptNoteLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if ($this->math->isZero((string) $line->accepted_quantity)) {
            return null;
        }

        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $unitCost = $this->costs->unitCostForReceiptLine($grn, $line);

        return $this->inventory->receive(new StockMovementData(
            tenantId: (int) $grn->tenant_id,
            movementDate: $grn->received_date->toDateString(),
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $line->item_id,
            warehouseId: (int) $grn->warehouse_id,
            quantity: (string) $line->accepted_quantity,
            organizationUnitId: $grn->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $grn->warehouse_location_id,
            unitCost: $unitCost,
            sourceType: 'goods_receipt_note',
            sourceId: (int) $grn->getKey(),
            sourceLineType: 'goods_receipt_note_line',
            sourceLineId: (int) $line->getKey(),
            description: 'GRN '.$grn->grn_number,
            uomId: $line->uom_id ?? $line->base_uom_id,
        ), $postedBy);
    }

    public function returnOut(PurchaseReturn $return, PurchaseReturnLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        return $this->inventory->issue(new StockMovementData(
            tenantId: (int) $return->tenant_id,
            movementDate: $return->return_date->toDateString(),
            movementType: InventoryMovementType::ReturnOut,
            direction: InventoryDirection::Out,
            itemId: (int) $line->item_id,
            warehouseId: (int) $return->warehouse_id,
            quantity: (string) $line->returned_quantity,
            organizationUnitId: $return->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $return->warehouse_location_id,
            unitCost: (string) $line->unit_price,
            sourceType: 'purchase_return',
            sourceId: (int) $return->getKey(),
            sourceLineType: 'purchase_return_line',
            sourceLineId: (int) $line->getKey(),
            description: 'Purchase return '.$return->return_number,
            uomId: $line->uom_id,
        ), $postedBy);
    }

    public function reverseReceipt(GoodsReceiptNote $grn, GoodsReceiptNoteLine $line, ?int $postedBy = null): ?InventoryMovement
    {
        if ($this->math->isZero((string) $line->accepted_quantity)) {
            return null;
        }

        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $line->loadMissing('inventoryMovement');
        if ($line->inventoryMovement instanceof InventoryMovement) {
            return $this->inventory->reverse($line->inventoryMovement, $postedBy);
        }

        return $this->inventory->issue(new StockMovementData(
            tenantId: (int) $grn->tenant_id,
            movementDate: now()->toDateString(),
            movementType: InventoryMovementType::ReturnOut,
            direction: InventoryDirection::Out,
            itemId: (int) $line->item_id,
            warehouseId: (int) $grn->warehouse_id,
            quantity: (string) $line->accepted_quantity,
            organizationUnitId: $grn->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $grn->warehouse_location_id,
            unitCost: (string) $line->unit_price,
            sourceType: 'goods_receipt_note_reversal',
            sourceId: (int) $grn->getKey(),
            sourceLineType: 'goods_receipt_note_line',
            sourceLineId: (int) $line->getKey(),
            description: 'GRN reversal '.$grn->grn_number,
            uomId: $line->uom_id ?? $line->base_uom_id,
        ), $postedBy);
    }

    private function affectsStock(int $itemId): bool
    {
        $item = Item::query()->findOrFail($itemId);

        return (bool) $item->is_stockable
            && ! in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true);
    }
}
