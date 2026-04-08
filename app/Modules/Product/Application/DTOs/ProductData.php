<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductData extends BaseDto
{
    public ?string $id = null;
    public ?string $categoryId = null;
    public string $sku = '';
    public ?string $barcode = null;
    public string $name = '';
    public ?string $description = null;
    public string $type = 'physical';
    public string $status = 'active';
    public string $unitOfMeasure = 'piece';
    public ?float $weight = null;
    public ?string $weightUnit = null;
    public ?float $dimensionsLength = null;
    public ?float $dimensionsWidth = null;
    public ?float $dimensionsHeight = null;
    public ?string $dimensionsUnit = null;
    public float $costPrice = 0.0;
    public float $sellingPrice = 0.0;
    public string $currencyCode = 'USD';
    public ?string $taxClass = null;
    public float $taxRate = 0.0;
    public bool $isTaxable = true;
    public bool $isTrackable = true;
    public bool $isPurchasable = true;
    public bool $isSellable = true;
    public float $minStockLevel = 0.0;
    public ?float $maxStockLevel = null;
    public float $reorderPoint = 0.0;
    public float $reorderQuantity = 0.0;
    public int $leadTimeDays = 0;
    public ?string $imagePath = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'sku'            => ['required', 'string', 'max:100'],
            'name'           => ['required', 'string', 'max:300'],
            'type'           => ['sometimes', 'string', 'in:physical,service,digital,combo,variable'],
            'status'         => ['sometimes', 'string', 'in:active,inactive,discontinued'],
            'cost_price'     => ['sometimes', 'numeric', 'min:0'],
            'selling_price'  => ['sometimes', 'numeric', 'min:0'],
            'currency_code'  => ['sometimes', 'string', 'max:10'],
        ];
    }
}
