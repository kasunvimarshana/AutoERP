<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Pricing\Domain\ValueObjects\PriceListType;
use Modules\Pricing\Domain\ValueObjects\PricingMethod;

class UpdatePriceListData extends BaseDto
{
    public int $id;
    public ?string $name = null;
    public ?string $code = null;
    public ?string $type = null;
    public ?string $pricingMethod = null;
    public ?string $currencyCode = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?bool $isActive = null;
    public ?string $description = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'            => 'required|integer',
            'name'          => 'sometimes|required|string|max:255',
            'code'          => 'sometimes|required|string|max:100',
            'type'          => 'sometimes|required|string|in:'.implode(',', PriceListType::values()),
            'pricingMethod' => 'sometimes|required|string|in:'.implode(',', PricingMethod::values()),
            'currencyCode'  => 'sometimes|required|string|size:3',
            'startDate'     => 'nullable|date',
            'endDate'       => 'nullable|date',
            'isActive'      => 'nullable|boolean',
            'description'   => 'nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
