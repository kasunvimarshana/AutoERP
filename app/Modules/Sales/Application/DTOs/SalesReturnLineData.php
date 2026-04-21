<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesReturnLineData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $toLocationId,
        public readonly int $uomId,
        public readonly ?int $salesReturnId = null,
        public readonly ?int $originalSalesOrderLineId = null,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?int $serialId = null,
        public readonly string $returnQty = '0.000000',
        public readonly string $unitPrice = '0.000000',
        public readonly string $lineTotal = '0.000000',
        public readonly string $condition = 'good',
        public readonly string $disposition = 'restock',
        public readonly string $restockingFee = '0.000000',
        public readonly ?string $qualityCheckNotes = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            productId: (int) $data['product_id'],
            toLocationId: (int) $data['to_location_id'],
            uomId: (int) $data['uom_id'],
            salesReturnId: isset($data['sales_return_id']) ? (int) $data['sales_return_id'] : null,
            originalSalesOrderLineId: isset($data['original_sales_order_line_id']) ? (int) $data['original_sales_order_line_id'] : null,
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            returnQty: isset($data['return_qty']) ? (string) $data['return_qty'] : '0.000000',
            unitPrice: isset($data['unit_price']) ? (string) $data['unit_price'] : '0.000000',
            lineTotal: isset($data['line_total']) ? (string) $data['line_total'] : '0.000000',
            condition: isset($data['condition']) ? (string) $data['condition'] : 'good',
            disposition: isset($data['disposition']) ? (string) $data['disposition'] : 'restock',
            restockingFee: isset($data['restocking_fee']) ? (string) $data['restocking_fee'] : '0.000000',
            qualityCheckNotes: isset($data['quality_check_notes']) ? (string) $data['quality_check_notes'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'sales_return_id' => $this->salesReturnId,
            'original_sales_order_line_id' => $this->originalSalesOrderLineId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'batch_id' => $this->batchId,
            'serial_id' => $this->serialId,
            'to_location_id' => $this->toLocationId,
            'uom_id' => $this->uomId,
            'return_qty' => $this->returnQty,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
            'condition' => $this->condition,
            'disposition' => $this->disposition,
            'restocking_fee' => $this->restockingFee,
            'quality_check_notes' => $this->qualityCheckNotes,
        ];
    }
}
