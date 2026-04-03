<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PriceListItemData extends BaseDto
{
    public int $tenantId;
    public int $priceListId;
    public int $productId;
    public ?int $variationId = null;
    public float $unitPrice;
    public float $minQuantity = 1.0;
    public ?float $maxQuantity = null;
    public float $discountPercent = 0.0;
    public float $markupPercent = 0.0;
    public string $currencyCode = 'USD';
    public ?string $uomCode = null;
    public bool $isActive = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'       => 'required|integer',
            'priceListId'    => 'required|integer',
            'productId'      => 'required|integer',
            'variationId'    => 'nullable|integer',
            'unitPrice'      => 'required|numeric|min:0',
            'minQuantity'    => 'required|numeric|min:0',
            'maxQuantity'    => 'nullable|numeric|min:0',
            'discountPercent'=> 'required|numeric|min:0|max:100',
            'markupPercent'  => 'required|numeric|min:0',
            'currencyCode'   => 'required|string|size:3',
            'uomCode'        => 'nullable|string|max:50',
            'isActive'       => 'boolean',
            'metadata'       => 'nullable|array',
        ];
    }
}
