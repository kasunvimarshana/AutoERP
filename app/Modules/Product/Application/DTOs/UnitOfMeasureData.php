<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UnitOfMeasureData extends BaseDto
{
    public ?string $id = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'piece';
    public bool $isBaseUnit = false;
    public float $conversionFactor = 1.0;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'in:weight,volume,length,area,time,piece,custom'],
            'conversion_factor' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
