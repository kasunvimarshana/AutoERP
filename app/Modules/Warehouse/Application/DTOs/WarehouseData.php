<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class WarehouseData extends BaseDto
{
    public ?string $id = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'standard';
    public ?string $description = null;
    public ?string $addressLine1 = null;
    public ?string $addressLine2 = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $postalCode = null;
    public ?string $country = null;
    public ?string $contactName = null;
    public ?string $contactEmail = null;
    public ?string $contactPhone = null;
    public bool $isActive = true;
    public bool $isDefault = false;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'type' => ['sometimes', 'string', 'in:standard,virtual,transit,external'],
        ];
    }
}
