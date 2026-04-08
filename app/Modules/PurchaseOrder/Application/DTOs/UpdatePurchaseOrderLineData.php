<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdatePurchaseOrderLineData extends BaseDto
{
    public int $id;
    public float $quantityOrdered;
    public float $unitPrice;
    public float $discountPercent = 0.0;
    public float $taxPercent = 0.0;
    public float $lineTotal = 0.0;
    public ?string $expectedDate = null;
    public ?string $notes = null;
    public ?array $metadata = null;
}
