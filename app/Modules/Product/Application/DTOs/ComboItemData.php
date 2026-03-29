<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ComboItemData extends BaseDto
{
    public int $product_id;

    public int $tenant_id;

    public int $component_product_id;

    public float $quantity;

    public ?float $price_override;

    public ?string $currency;

    public int $sort_order;

    public ?array $metadata;

    public function __construct()
    {
        $this->sort_order = 0;
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'product_id'           => 'required|integer',
            'tenant_id'            => 'required|integer',
            'component_product_id' => 'required|integer',
            'quantity'             => 'required|numeric|min:0.0001',
            'price_override'       => 'nullable|numeric|min:0',
            'currency'             => 'nullable|string|size:3',
            'sort_order'           => 'nullable|integer|min:0',
            'metadata'             => 'nullable|array',
        ];
    }
}
