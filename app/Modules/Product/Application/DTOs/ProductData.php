<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Product\Domain\ValueObjects\ProductType;

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

    public string $type;

    public ?array $units_of_measure;

    public ?array $attributes;

    public ?array $metadata;

    /** @var array<array{code: string, name: string, allowed_values?: string[]}>|null */
    public ?array $product_attributes;

    public function __construct()
    {
        $this->currency = 'USD';
        $this->status   = 'active';
        $this->type     = ProductType::PHYSICAL;
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'                           => 'required|integer|exists:tenants,id',
            'sku'                                 => 'required|string|max:100',
            'name'                                => 'required|string|max:255',
            'description'                         => 'nullable|string',
            'price'                               => 'required|numeric|min:0',
            'currency'                            => 'nullable|string|size:3',
            'category'                            => 'nullable|string|max:100',
            'status'                              => 'nullable|string|in:active,inactive,draft',
            'type'                                => 'nullable|string|in:'.implode(',', ProductType::VALID_TYPES),
            'units_of_measure'                    => 'nullable|array',
            'units_of_measure.*.unit'             => 'required_with:units_of_measure|string|max:50',
            'units_of_measure.*.type'             => 'required_with:units_of_measure|string|in:buying,selling,inventory',
            'units_of_measure.*.conversion_factor' => 'nullable|numeric|min:0.0001',
            'attributes'                          => 'nullable|array',
            'metadata'                            => 'nullable|array',
            'product_attributes'                  => 'nullable|array',
            'product_attributes.*.code'           => 'required_with:product_attributes|string|max:50',
            'product_attributes.*.name'           => 'required_with:product_attributes|string|max:100',
            'product_attributes.*.allowed_values' => 'nullable|array',
            'product_attributes.*.allowed_values.*' => 'string|max:100',
        ];
    }
}
