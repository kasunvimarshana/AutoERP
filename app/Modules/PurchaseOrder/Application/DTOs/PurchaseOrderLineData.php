<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PurchaseOrderLineData extends BaseDto
{
    public int $tenantId;
    public int $purchaseOrderId;
    public int $lineNumber;
    public int $productId;
    public float $quantityOrdered;
    public float $unitPrice;
    public ?int $variationId = null;
    public ?string $description = null;
    public ?int $uomId = null;
    public float $discountPercent = 0.0;
    public float $taxPercent = 0.0;
    public float $lineTotal = 0.0;
    public ?string $expectedDate = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'open';
}
