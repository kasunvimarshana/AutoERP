<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PurchaseOrderData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public int $supplierId;
    public string $orderDate;
    public ?string $supplierReference = null;
    public ?string $expectedDate = null;
    public ?int $warehouseId = null;
    public string $currency = 'USD';
    public float $subtotal = 0.0;
    public float $taxAmount = 0.0;
    public float $discountAmount = 0.0;
    public float $totalAmount = 0.0;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';
}
