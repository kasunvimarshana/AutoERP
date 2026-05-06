<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\CreateSupplierProductServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierProductData;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class CreateSupplierProductService extends BaseService implements CreateSupplierProductServiceInterface
{
    public function __construct(
        private readonly SupplierProductRepositoryInterface $supplierProductRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierProductRepository);
    }

    protected function handle(array $data): SupplierProduct
    {
        $dto = SupplierProductData::fromArray($data);

        $supplier = $this->supplierRepository->find($dto->supplier_id);
        if (! $supplier) {
            throw new SupplierNotFoundException($dto->supplier_id);
        }

        $supplierProduct = new SupplierProduct(
            tenantId: $supplier->getTenantId(),
            supplierId: $dto->supplier_id,
            productId: $dto->product_id,
            variantId: $dto->variant_id,
            supplierSku: $dto->supplier_sku,
            leadTimeDays: $dto->lead_time_days,
            minOrderQty: $dto->min_order_qty,
            isPreferred: $dto->is_preferred,
            lastPurchasePrice: $dto->last_purchase_price,
        );

        if ($dto->is_preferred) {
            $this->supplierProductRepository->clearPreferredByProductVariant(
                tenantId: $supplier->getTenantId(),
                productId: $dto->product_id,
                variantId: $dto->variant_id,
            );
        }

        return $this->supplierProductRepository->save($supplierProduct);
    }
}
