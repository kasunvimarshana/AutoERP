<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventorySettingData extends BaseDto
{
    public int $id;

    public ?string $valuationMethod = null;

    public ?string $managementMethod = null;

    public ?string $rotationStrategy = null;

    public ?string $allocationAlgorithm = null;

    public ?string $cycleCountMethod = null;

    public ?bool $negativeStockAllowed = null;

    public ?bool $trackLots = null;

    public ?bool $trackSerialNumbers = null;

    public ?bool $trackExpiry = null;

    public ?bool $autoReorder = null;

    public ?bool $lowStockAlert = null;

    public ?array $metadata = null;

    public ?bool $isActive = null;

    public function rules(): array
    {
        return [
            'id'                  => 'required|integer',
            'valuationMethod'     => 'sometimes|string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'managementMethod'    => 'sometimes|string|in:perpetual,periodic',
            'rotationStrategy'    => 'sometimes|string|in:fefo,fifo,lifo,manual',
            'allocationAlgorithm' => 'sometimes|string|in:fefo,fifo,lifo,nearest_expiry,manual',
            'cycleCountMethod'    => 'sometimes|string|in:abc,frequency,random,manual',
            'negativeStockAllowed'=> 'sometimes|boolean',
            'trackLots'           => 'sometimes|boolean',
            'trackSerialNumbers'  => 'sometimes|boolean',
            'trackExpiry'         => 'sometimes|boolean',
            'autoReorder'         => 'sometimes|boolean',
            'lowStockAlert'       => 'sometimes|boolean',
            'metadata'            => 'nullable|array',
            'isActive'            => 'sometimes|boolean',
        ];
    }
}
