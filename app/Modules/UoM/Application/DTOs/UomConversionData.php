<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UomConversionData extends BaseDto
{
    public int $tenantId;

    public int $fromUomId;

    public int $toUomId;

    public float $factor;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'tenantId'   => 'required|integer|exists:tenants,id',
            'fromUomId'  => 'required|integer|exists:units_of_measure,id',
            'toUomId'    => 'required|integer|exists:units_of_measure,id',
            'factor'     => 'required|numeric|min:0',
            'isActive'   => 'boolean',
        ];
    }
}
