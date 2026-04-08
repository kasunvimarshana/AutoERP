<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Warehouse\Domain\ValueObjects\WarehouseType;

final class WarehouseData extends BaseDto
{
    public string  $name;
    public string  $code;
    public string  $type      = WarehouseType::STANDARD;
    public bool    $is_active = true;
    public ?array  $address   = null;
    public ?array  $metadata  = null;

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50'],
            'type'      => ['required', 'string', 'in:' . implode(',', WarehouseType::ALL)],
            'address'   => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
