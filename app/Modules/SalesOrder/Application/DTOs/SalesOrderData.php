<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class SalesOrderData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public int $customerId;
    public string $orderDate;
    public ?string $customerReference = null;
    public ?string $requiredDate = null;
    public ?int $warehouseId = null;
    public string $currency = 'USD';
    public float $subtotal = 0.0;
    public float $taxAmount = 0.0;
    public float $discountAmount = 0.0;
    public float $totalAmount = 0.0;
    public ?array $shippingAddress = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';
}
