<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductData extends BaseDto
{
    public ?int $tenant_id = null;
    public ?int $category_id = null;
    public ?int $unit_of_measure_id = null;
    public string $sku = '';
    public ?string $barcode = null;
    public string $name = '';
    public ?string $slug = null;
    public ?string $short_description = null;
    public ?string $description = null;
    public string $type = 'physical';
    public string $status = 'draft';
    public bool $is_purchasable = true;
    public bool $is_sellable = true;
    public bool $is_stockable = true;
    public bool $has_variants = false;
    public bool $has_serial_tracking = false;
    public bool $has_batch_tracking = false;
    public bool $has_expiry_tracking = false;
    public float $cost_price = 0.0;
    public float $selling_price = 0.0;
    public ?float $min_selling_price = null;
    public string $currency = 'USD';
    public ?string $tax_class = null;
    public ?float $weight = null;
    public ?string $weight_unit = null;
    public ?array $dimensions = null;
    public ?array $images = null;
    public ?array $tags = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'sku'            => 'required|string|max:100',
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:physical,service,digital,combo,variable',
            'status'         => 'required|in:active,inactive,draft,discontinued',
            'cost_price'     => 'nullable|numeric|min:0',
            'selling_price'  => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|size:3',
        ];
    }
}
