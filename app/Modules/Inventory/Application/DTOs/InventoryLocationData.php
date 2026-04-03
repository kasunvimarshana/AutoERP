<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryLocationData extends BaseDto
{
    public int $tenantId;

    public int $warehouseId;

    public ?int $zoneId = null;

    public ?string $code = null;

    public string $name;

    public string $type = 'bin';

    public ?string $aisle = null;

    public ?string $row = null;

    public ?string $level = null;

    public ?string $bin = null;

    public ?float $capacity = null;

    public ?float $weightLimit = null;

    public ?string $barcode = null;

    public ?string $qrCode = null;

    public bool $isPickable = true;

    public bool $isStorable = true;

    public bool $isPacking = false;

    public bool $isActive = true;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'    => 'required|integer',
            'warehouseId' => 'required|integer',
            'zoneId'      => 'nullable|integer',
            'code'        => 'nullable|string|max:100',
            'name'        => 'required|string|max:255',
            'type'        => 'string|in:bin,rack,shelf,floor,receiving,shipping,staging,quarantine',
            'aisle'       => 'nullable|string|max:50',
            'row'         => 'nullable|string|max:50',
            'level'       => 'nullable|string|max:50',
            'bin'         => 'nullable|string|max:50',
            'capacity'    => 'nullable|numeric|min:0',
            'weightLimit' => 'nullable|numeric|min:0',
            'barcode'     => 'nullable|string|max:255',
            'qrCode'      => 'nullable|string|max:255',
            'isPickable'  => 'boolean',
            'isStorable'  => 'boolean',
            'isPacking'   => 'boolean',
            'isActive'    => 'boolean',
            'metadata'    => 'nullable|array',
        ];
    }
}
