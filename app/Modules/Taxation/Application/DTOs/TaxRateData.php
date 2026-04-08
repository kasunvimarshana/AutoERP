<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TaxRateData extends BaseDto
{
    public int $tenantId;
    public string $name;
    public string $code;
    public string $taxType;
    public string $calculationMethod = 'exclusive';
    public float $rate;
    public ?string $jurisdiction = null;
    public bool $isActive = true;
    public ?string $description = null;
    public ?string $effectiveFrom = null;
    public ?string $effectiveTo = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'          => 'required|integer',
            'name'              => 'required|string|max:255',
            'code'              => 'required|string|max:100',
            'taxType'           => 'required|string|in:vat,gst,sales_tax,excise,customs,withholding,service_tax,income_tax',
            'calculationMethod' => 'required|string|in:inclusive,exclusive,compound',
            'rate'              => 'required|numeric|min:0|max:100',
            'jurisdiction'      => 'nullable|string|max:255',
            'isActive'          => 'boolean',
            'description'       => 'nullable|string',
            'effectiveFrom'     => 'nullable|date',
            'effectiveTo'       => 'nullable|date',
            'metadata'          => 'nullable|array',
        ];
    }
}
