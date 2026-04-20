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
        if ($supplierProduct->getSupplierId() !== $dto->supplier_id) {
            throw new SupplierProductNotFoundException($id);
        }

        $supplier = $this->supplierRepository->find($dto->supplier_id);
        if (! $supplier || $supplier->getTenantId() !== $supplierProduct->getTenantId()) {
            throw new SupplierNotFoundException($dto->supplier_id);
        }

        $supplierProduct->update(
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
                excludeId: $id,
            );
        }

        return $this->supplierProductRepository->save($supplierProduct);
    }
}
