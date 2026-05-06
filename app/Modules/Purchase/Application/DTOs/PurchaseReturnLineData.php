<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseReturnLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseReturnId,
        public readonly int $productId,
        public readonly int $fromLocationId,
        public readonly int $uomId,
        public readonly string $returnQty,
        public readonly string $unitCost,
        public readonly string $condition,
        public readonly string $disposition,
        public readonly string $restockingFee = '0',
        public readonly ?int $originalGrnLineId = null,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?int $serialId = null,
        public readonly ?string $qualityCheckNotes = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            purchaseReturnId: (int) $data['purchase_return_id'],
            productId: (int) $data['product_id'],
            fromLocationId: (int) $data['from_location_id'],
            uomId: (int) $data['uom_id'],
            returnQty: (string) $data['return_qty'],
            unitCost: (string) $data['unit_cost'],
            condition: (string) $data['condition'],
            disposition: (string) $data['disposition'],
            restockingFee: isset($data['restocking_fee']) ? (string) $data['restocking_fee'] : '0',
            originalGrnLineId: isset($data['original_grn_line_id']) ? (int) $data['original_grn_line_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            qualityCheckNotes: isset($data['quality_check_notes']) ? (string) $data['quality_check_notes'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'purchase_return_id' => $this->purchaseReturnId,
            'product_id' => $this->productId,
            'from_location_id' => $this->fromLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => $this->returnQty,
            'unit_cost' => $this->unitCost,
            'condition' => $this->condition,
            'disposition' => $this->disposition,
            'restocking_fee' => $this->restockingFee,
            'original_grn_line_id' => $this->originalGrnLineId,
            'variant_id' => $this->variantId,
            'batch_id' => $this->batchId,
            'serial_id' => $this->serialId,
            'quality_check_notes' => $this->qualityCheckNotes,
        ];
    }
}
