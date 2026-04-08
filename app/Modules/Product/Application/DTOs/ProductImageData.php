<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductImageData extends BaseDto
{
    public int $product_id;

    public int $tenant_id;

    public ?int $sort_order;

    public bool $is_primary;

    public ?array $metadata;

    public function __construct()
    {
        $this->sort_order = 0;
        $this->is_primary = false;
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'boolean',
            'metadata'   => 'nullable|array',
        ];
    }
}
