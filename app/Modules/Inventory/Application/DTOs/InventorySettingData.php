<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventorySettingData extends BaseDto
{
    public int $tenantId;

    public string $valuationMethod = 'fifo';

    public string $managementMethod = 'perpetual';

    public string $rotationStrategy = 'fefo';

    public string $allocationAlgorithm = 'fefo';

    public string $cycleCountMethod = 'abc';

    public bool $negativeStockAllowed = false;

    public bool $trackLots = true;

    public bool $trackSerialNumbers = true;

    public bool $trackExpiry = true;

    public bool $autoReorder = false;

    public bool $lowStockAlert = true;

    public ?array $metadata = null;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'tenantId'            => 'required|integer',
            'valuationMethod'     => 'string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'managementMethod'    => 'string|in:perpetual,periodic',
            'rotationStrategy'    => 'string|in:fefo,fifo,lifo,manual',
            'allocationAlgorithm' => 'string|in:fefo,fifo,lifo,nearest_expiry,manual',
            'cycleCountMethod'    => 'string|in:abc,frequency,random,manual',
            'negativeStockAllowed'=> 'boolean',
            'trackLots'           => 'boolean',
            'trackSerialNumbers'  => 'boolean',
            'trackExpiry'         => 'boolean',
            'autoReorder'         => 'boolean',
            'lowStockAlert'       => 'boolean',
            'metadata'            => 'nullable|array',
            'isActive'            => 'boolean',
        ];
    }
}
