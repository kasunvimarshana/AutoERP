<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductData extends BaseDto
{
    public int $tenant_id;

    public string $sku;

    public string $name;

    public ?string $description;

    public float $price;

    public string $currency;

    public ?string $category;

    public string $status;

    public ?array $attributes;

    public ?array $metadata;

    public function __construct()
    {
        $this->currency = 'USD';
        $this->status = 'active';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'sku'         => 'required|string|max:100',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'currency'    => 'nullable|string|size:3',
            'category'    => 'nullable|string|max:100',
            'status'      => 'nullable|string|in:active,inactive,draft',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
