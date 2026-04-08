<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class WarehouseLocationData extends BaseDto
{
    public ?string $id = null;
    public string $warehouseId = '';
    public ?string $parentId = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'bin';
    public ?string $barcode = null;
    public ?string $description = null;
    public bool $isActive = true;
    public bool $isPickable = true;
    public bool $isReceivable = true;
    public ?float $maxWeight = null;
    public ?float $maxVolume = null;
    public int $sortOrder = 0;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'string'],
            'code'         => ['required', 'string', 'max:100'],
            'name'         => ['required', 'string', 'max:200'],
            'type'         => ['sometimes', 'string', 'in:zone,aisle,rack,shelf,bin'],
        ];
    }
}
