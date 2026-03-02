<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Data Transfer Object for creating a VendorBill.
 *
 * All monetary amounts MUST be passed as numeric strings for BCMath precision.
 */
final class CreateVendorBillDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $vendorId,
        public readonly ?int $purchaseOrderId,
        public readonly string $billDate,
        public readonly ?string $dueDate,
        public readonly string $totalAmount,
        public readonly ?string $notes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            vendorId: (int) $data['vendor_id'],
            purchaseOrderId: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            billDate: (string) $data['bill_date'],
            dueDate: isset($data['due_date']) ? (string) $data['due_date'] : null,
            totalAmount: (string) $data['total_amount'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id'         => $this->vendorId,
            'purchase_order_id' => $this->purchaseOrderId,
            'bill_date'         => $this->billDate,
            'due_date'          => $this->dueDate,
            'total_amount'      => $this->totalAmount,
            'notes'             => $this->notes,
        ];
    }
}
