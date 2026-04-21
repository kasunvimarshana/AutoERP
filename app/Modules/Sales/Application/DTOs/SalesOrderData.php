<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

class SalesOrderData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  array<int, mixed>|null  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly int $currencyId,
        public readonly ?int $orgUnitId = null,
        public readonly ?string $soNumber = null,
        public readonly string $status = 'draft',
        public readonly string $exchangeRate = '1.000000',
        public readonly ?string $orderDate = null,
        public readonly ?string $requestedDeliveryDate = null,
        public readonly ?int $priceListId = null,
        public readonly string $subtotal = '0.000000',
        public readonly string $taxTotal = '0.000000',
        public readonly string $discountTotal = '0.000000',
        public readonly string $grandTotal = '0.000000',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $createdBy = null,
        public readonly ?int $approvedBy = null,
        public readonly ?array $lines = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            customerId: (int) $data['customer_id'],
            warehouseId: (int) $data['warehouse_id'],
            currencyId: (int) $data['currency_id'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            soNumber: isset($data['so_number']) ? (string) $data['so_number'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1.000000',
            orderDate: isset($data['order_date']) ? (string) $data['order_date'] : null,
            requestedDeliveryDate: isset($data['requested_delivery_date']) ? (string) $data['requested_delivery_date'] : null,
            priceListId: isset($data['price_list_id']) ? (int) $data['price_list_id'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0.000000',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0.000000',
            discountTotal: isset($data['discount_total']) ? (string) $data['discount_total'] : '0.000000',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0.000000',
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
            approvedBy: isset($data['approved_by']) ? (int) $data['approved_by'] : null,
            lines: isset($data['lines']) && is_array($data['lines']) ? $data['lines'] : null,
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
            'customer_id' => $this->customerId,
            'org_unit_id' => $this->orgUnitId,
            'warehouse_id' => $this->warehouseId,
            'so_number' => $this->soNumber,
            'status' => $this->status,
            'currency_id' => $this->currencyId,
            'exchange_rate' => $this->exchangeRate,
            'order_date' => $this->orderDate,
            'requested_delivery_date' => $this->requestedDeliveryDate,
            'price_list_id' => $this->priceListId,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'discount_total' => $this->discountTotal,
            'grand_total' => $this->grandTotal,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_by' => $this->createdBy,
            'approved_by' => $this->approvedBy,
        ];
    }
}
