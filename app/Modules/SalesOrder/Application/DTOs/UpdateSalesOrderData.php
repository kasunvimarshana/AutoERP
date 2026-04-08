<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateSalesOrderData extends BaseDto
{
    public int $id;
    public ?string $customerReference = null;
    public ?string $requiredDate = null;
    public ?int $warehouseId = null;
    public ?array $shippingAddress = null;
    public ?string $notes = null;
    public ?array $metadata = null;
}
