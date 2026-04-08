<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductVariantData extends BaseDto
{
    public ?string $id = null;
    public string $productId = '';
    public string $sku = '';
    public ?string $barcode = null;
    public string $name = '';
    public ?array $attributes = null;
    public float $costPrice = 0.0;
    public float $sellingPrice = 0.0;
    public ?float $weight = null;
    public ?string $imagePath = null;
    public bool $isActive = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string'],
            'sku'        => ['required', 'string', 'max:100'],
            'name'       => ['required', 'string', 'max:300'],
        ];
    }
}
