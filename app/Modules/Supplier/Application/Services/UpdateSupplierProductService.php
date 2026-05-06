<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Supplier\Application\Contracts\UpdateSupplierProductServiceInterface;
use Modules\Supplier\Application\DTOs\SupplierProductData;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\Exceptions\SupplierNotFoundException;
use Modules\Supplier\Domain\Exceptions\SupplierProductNotFoundException;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierRepositoryInterface;

class UpdateSupplierProductService extends BaseService implements UpdateSupplierProductServiceInterface
{
    public function __construct(
        private readonly SupplierProductRepositoryInterface $supplierProductRepository,
        private readonly SupplierRepositoryInterface $supplierRepository,
    ) {
        parent::__construct($supplierProductRepository);
    }

    protected function handle(array $data): SupplierProduct
    {
        $id = (int) ($data['id'] ?? 0);
        $supplierProduct = $this->supplierProductRepository->find($id);
        if (! $supplierProduct) {
            throw new SupplierProductNotFoundException($id);
        }

        $dto = SupplierProductData::fromArray($data);
        if ($supplierProduct->getSupplierId() !== $dto->supplierId) {
            throw new SupplierProductNotFoundException($id);
        }

        $supplier = $this->supplierRepository->find($dto->supplierId);
        if (! $supplier || $supplier->getTenantId() !== $supplierProduct->getTenantId()) {
            throw new SupplierNotFoundException($dto->supplierId);
        }

        $supplierProduct->update(
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
                excludeId: $id,
            );
        }

        return $this->supplierProductRepository->save($supplierProduct);
    }
}
