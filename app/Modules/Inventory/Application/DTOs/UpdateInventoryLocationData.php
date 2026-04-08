<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventoryLocationData extends BaseDto
{
    public int $id;

    public ?string $code = null;

    public ?string $name = null;

    public ?string $type = null;

    public ?string $aisle = null;

    public ?string $row = null;

    public ?string $level = null;

    public ?string $bin = null;

    public ?float $capacity = null;

    public ?float $weightLimit = null;

    public ?string $barcode = null;

    public ?string $qrCode = null;

    public ?bool $isPickable = null;

    public ?bool $isStorable = null;

    public ?bool $isPacking = null;

    public ?bool $isActive = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'          => 'required|integer',
            'code'        => 'sometimes|nullable|string|max:100',
            'name'        => 'sometimes|required|string|max:255',
            'type'        => 'sometimes|string|in:bin,rack,shelf,floor,receiving,shipping,staging,quarantine',
            'aisle'       => 'nullable|string|max:50',
            'row'         => 'nullable|string|max:50',
            'level'       => 'nullable|string|max:50',
            'bin'         => 'nullable|string|max:50',
            'capacity'    => 'nullable|numeric|min:0',
            'weightLimit' => 'nullable|numeric|min:0',
            'barcode'     => 'nullable|string|max:255',
            'qrCode'      => 'nullable|string|max:255',
            'isPickable'  => 'sometimes|boolean',
            'isStorable'  => 'sometimes|boolean',
            'isPacking'   => 'sometimes|boolean',
            'isActive'    => 'sometimes|boolean',
            'metadata'    => 'nullable|array',
        ];
    }
}
