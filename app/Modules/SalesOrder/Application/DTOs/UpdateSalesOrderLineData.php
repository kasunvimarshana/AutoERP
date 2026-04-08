<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateSalesOrderLineData extends BaseDto
{
    public int $id;
    public float $quantity;
    public float $unitPrice;
    public float $taxRate = 0.0;
    public float $discountAmount = 0.0;
    public float $totalAmount = 0.0;
    public ?int $warehouseLocationId = null;
    public ?string $batchNumber = null;
    public ?string $serialNumber = null;
    public ?string $description = null;
    public ?string $notes = null;
    public ?array $metadata = null;
}
