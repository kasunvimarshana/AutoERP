<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdatePurchaseOrderData extends BaseDto
{
    public int $id;
    public ?string $supplierReference = null;
    public ?string $expectedDate = null;
    public ?int $warehouseId = null;
    public ?string $notes = null;
    public ?array $metadata = null;
}
