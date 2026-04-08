<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductVariantData extends BaseDto
{
    public ?int $tenant_id = null;
    public ?int $product_id = null;
    public string $sku = '';
    public ?string $barcode = null;
    public ?string $name = null;
    public array $attribute_values = [];
    public ?float $cost_price = null;
    public ?float $selling_price = null;
    public ?float $weight = null;
    public bool $is_active = true;
    public ?array $images = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'product_id'       => 'required|integer',
            'sku'              => 'required|string|max:100',
            'attribute_values' => 'required|array',
        ];
    }
}
