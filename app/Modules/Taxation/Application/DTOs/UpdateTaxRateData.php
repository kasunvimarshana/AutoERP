<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateTaxRateData extends BaseDto
{
    public int $id;
    public ?string $name = null;
    public ?string $code = null;
    public ?string $taxType = null;
    public ?string $calculationMethod = null;
    public ?float $rate = null;
    public ?string $jurisdiction = null;
    public ?bool $isActive = null;
    public ?string $description = null;
    public ?string $effectiveFrom = null;
    public ?string $effectiveTo = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'                => 'required|integer',
            'name'              => 'sometimes|nullable|string|max:255',
            'code'              => 'sometimes|nullable|string|max:100',
            'taxType'           => 'sometimes|nullable|string|in:vat,gst,sales_tax,excise,customs,withholding,service_tax,income_tax',
            'calculationMethod' => 'sometimes|nullable|string|in:inclusive,exclusive,compound',
            'rate'              => 'sometimes|nullable|numeric|min:0|max:100',
            'jurisdiction'      => 'nullable|string|max:255',
            'isActive'          => 'sometimes|nullable|boolean',
            'description'       => 'nullable|string',
            'effectiveFrom'     => 'nullable|date',
            'effectiveTo'       => 'nullable|date',
            'metadata'          => 'nullable|array',
        ];
    }
}
