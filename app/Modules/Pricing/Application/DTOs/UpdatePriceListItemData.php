<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdatePriceListItemData extends BaseDto
{
    public int $id;
    public ?int $productId = null;
    public ?int $variationId = null;
    public ?float $unitPrice = null;
    public ?float $minQuantity = null;
    public ?float $maxQuantity = null;
    public ?float $discountPercent = null;
    public ?float $markupPercent = null;
    public ?string $currencyCode = null;
    public ?string $uomCode = null;
    public ?bool $isActive = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'             => 'required|integer',
            'productId'      => 'sometimes|required|integer',
            'variationId'    => 'nullable|integer',
            'unitPrice'      => 'sometimes|required|numeric|min:0',
            'minQuantity'    => 'sometimes|required|numeric|min:0',
            'maxQuantity'    => 'nullable|numeric|min:0',
            'discountPercent'=> 'nullable|numeric|min:0|max:100',
            'markupPercent'  => 'nullable|numeric|min:0',
            'currencyCode'   => 'sometimes|required|string|size:3',
            'uomCode'        => 'nullable|string|max:50',
            'isActive'       => 'nullable|boolean',
            'metadata'       => 'nullable|array',
        ];
    }
}
