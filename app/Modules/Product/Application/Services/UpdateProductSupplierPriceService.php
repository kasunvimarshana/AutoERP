<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateProductSupplierPriceServiceInterface;
use Modules\Product\Application\DTOs\ProductSupplierPriceData;
use Modules\Product\Domain\Entities\ProductSupplierPrice;
use Modules\Product\Domain\Exceptions\ProductSupplierPriceNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductSupplierPriceRepositoryInterface;

class UpdateProductSupplierPriceService extends BaseService implements UpdateProductSupplierPriceServiceInterface
{
    public function __construct(private readonly ProductSupplierPriceRepositoryInterface $productSupplierPriceRepository)
    {
        parent::__construct($productSupplierPriceRepository);
    }

    protected function handle(array $data): ProductSupplierPrice
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->productSupplierPriceRepository->find($id);

        if (! $entity) {
            throw new ProductSupplierPriceNotFoundException($id);
        }

        $dto = ProductSupplierPriceData::fromArray($data);
        $entity->update(
            supplierId: $dto->supplier_id,
            uomId: $dto->uom_id,
            unitPrice: $dto->unit_price,
            variantId: $dto->variant_id,
            currencyId: $dto->currency_id,
            minOrderQuantity: $dto->min_order_quantity,
            discountPercent: $dto->discount_percent,
            leadTimeDays: $dto->lead_time_days,
            isPreferred: $dto->is_preferred,
            isActive: $dto->is_active,
            effectiveFrom: $dto->effective_from !== null ? new \DateTimeImmutable($dto->effective_from) : null,
            effectiveTo: $dto->effective_to !== null ? new \DateTimeImmutable($dto->effective_to) : null,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->productSupplierPriceRepository->save($entity);
    }
}
