<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Domain\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;
use Modules\Inventory\Domain\Events\ProductVariantCreated;

class CreateProductVariantUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private ProductVariantRepositoryInterface $variantRepo,
    ) {}

    public function execute(array $data): object
    {
        if (empty(trim($data['product_id'] ?? ''))) {
            throw new DomainException('Product is required for a variant.');
        }

        if (empty(trim($data['sku'] ?? ''))) {
            throw new DomainException('SKU is required for a variant.');
        }

        if (empty(trim($data['name'] ?? ''))) {
            throw new DomainException('Name is required for a variant.');
        }

        $product = $this->productRepo->findById($data['product_id']);
        if (!$product) {
            throw new DomainException('Product not found.');
        }

        $tenantId = $data['tenant_id'] ?? '';
        $existing = $this->variantRepo->findBySku($tenantId, $data['sku']);
        if ($existing) {
            throw new DomainException('A variant with this SKU already exists.');
        }

        // BCMath normalisation for decimal fields
        $data['unit_price'] = bcadd($data['unit_price'] ?? '0', '0', 8);
        $data['cost_price'] = bcadd($data['cost_price'] ?? '0', '0', 8);

        if (!isset($data['attributes'])) {
            $data['attributes'] = [];
        }
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return DB::transaction(function () use ($data) {
            $variant = $this->variantRepo->create($data);
            Event::dispatch(new ProductVariantCreated($variant->id));
            return $variant;
        });
    }
}
