<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Pricing\Application\Contracts\CreateSupplierPriceListServiceInterface;
use Modules\Pricing\Application\DTOs\SupplierPriceListData;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;

class CreateSupplierPriceListService extends BaseService implements CreateSupplierPriceListServiceInterface
{
    public function __construct(
        private readonly SupplierPriceListRepositoryInterface $supplierPriceListRepository,
        private readonly PriceListRepositoryInterface $priceListRepository,
    ) {
        parent::__construct($supplierPriceListRepository);
    }

    protected function handle(array $data): SupplierPriceList
    {
        $dto = SupplierPriceListData::fromArray($data);

        $priceList = $this->priceListRepository->find($dto->price_list_id);
        if (! $priceList) {
            throw new DomainException('Price list not found.');
        }

        if ($priceList->getType() !== 'purchase') {
            throw new DomainException('Only purchase price lists can be assigned to suppliers.');
        }

        $assignment = new SupplierPriceList(
            tenantId: $priceList->getTenantId(),
            supplierId: $dto->supplier_id,
            priceListId: $dto->price_list_id,
            priority: $dto->priority,
        );

        return $this->supplierPriceListRepository->save($assignment);
    }
}
