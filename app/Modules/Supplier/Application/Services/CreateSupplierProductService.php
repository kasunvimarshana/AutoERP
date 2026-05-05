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

        $supplier = $this->supplierRepository->find($dto->supplierId);
        if (! $supplier) {
            throw new SupplierNotFoundException($dto->supplierId);
        }

        $supplierProduct = new SupplierProduct(
            tenantId: $supplier->getTenantId(),
            supplierId: $dto->supplierId,
            productId: $dto->productId,
            variantId: $dto->variantId,
            supplierSku: $dto->supplierSku,
            leadTimeDays: $dto->leadTimeDays,
            minOrderQty: $dto->minOrderQty,
            isPreferred: $dto->isPreferred,
            lastPurchasePrice: $dto->lastPurchasePrice,
        );

        if ($dto->isPreferred) {
            $this->supplierProductRepository->clearPreferredByProductVariant(
                tenantId: $supplier->getTenantId(),
                productId: $dto->productId,
                variantId: $dto->variantId,
            );
        }

        return $this->supplierProductRepository->save($supplierProduct);
    }
}
