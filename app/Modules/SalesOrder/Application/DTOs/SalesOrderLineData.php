<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class SalesOrderLineData extends BaseDto
{
    public int $tenantId;
    public int $salesOrderId;
    public int $productId;
    public float $quantity;
    public float $unitPrice;
    public ?int $productVariantId = null;
    public ?string $description = null;
    public float $taxRate = 0.0;
    public float $discountAmount = 0.0;
    public float $totalAmount = 0.0;
    public ?string $unitOfMeasure = null;
    public string $status = 'pending';
    public ?int $warehouseLocationId = null;
    public ?string $batchNumber = null;
    public ?string $serialNumber = null;
    public ?string $notes = null;
    public ?array $metadata = null;
}
