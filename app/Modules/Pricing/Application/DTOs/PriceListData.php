<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Pricing\Domain\ValueObjects\PriceListType;
use Modules\Pricing\Domain\ValueObjects\PricingMethod;

class PriceListData extends BaseDto
{
    public int $tenantId;
    public string $name;
    public string $code;
    public string $type;
    public string $pricingMethod = 'fixed';
    public string $currencyCode = 'USD';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public bool $isActive = true;
    public ?string $description = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'      => 'required|integer',
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:100',
            'type'          => 'required|string|in:'.implode(',', PriceListType::values()),
            'pricingMethod' => 'required|string|in:'.implode(',', PricingMethod::values()),
            'currencyCode'  => 'required|string|size:3',
            'startDate'     => 'nullable|date',
            'endDate'       => 'nullable|date',
            'isActive'      => 'boolean',
            'description'   => 'nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
