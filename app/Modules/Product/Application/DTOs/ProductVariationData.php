<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductVariationData extends BaseDto
{
    public int $product_id;

    public int $tenant_id;

    public string $sku;

    public string $name;

    public float $price;

    public string $currency;

    public ?array $attribute_values;

    public string $status;

    public int $sort_order;

    public ?array $metadata;

    public function __construct()
    {
        $this->currency   = 'USD';
        $this->status     = 'active';
        $this->sort_order = 0;
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'product_id'         => 'required|integer',
            'tenant_id'          => 'required|integer',
            'sku'                => 'required|string|max:100',
            'name'               => 'required|string|max:255',
            'price'              => 'required|numeric|min:0',
            'currency'           => 'nullable|string|size:3',
            'attribute_values'   => 'nullable|array',
            'status'             => 'nullable|string|in:active,inactive',
            'sort_order'         => 'nullable|integer|min:0',
            'metadata'           => 'nullable|array',
        ];
    }
}
