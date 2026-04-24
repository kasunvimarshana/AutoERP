<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class UpdatePriceListService extends BaseService implements UpdatePriceListServiceInterface
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceListRepository,
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    )
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): PriceList
    {
        $id = (int) ($data['id'] ?? 0);
        $priceList = $this->priceListRepository->find($id);

        if (! $priceList) {
            throw new PriceListNotFoundException($id);
        }

        $dto = PriceListData::fromArray($data);

        if ($priceList->getTenantId() !== $dto->tenant_id) {
            throw new PriceListNotFoundException($id);
        }

        $priceList->update(
            name: $dto->name,
            type: $dto->type,
            currencyId: $dto->currency_id,
            isDefault: $dto->is_default,
            validFrom: $this->toDate($dto->valid_from),
            validTo: $this->toDate($dto->valid_to),
            isActive: $dto->is_active,
        );

        if ($dto->is_default) {
            $this->priceListRepository->clearDefaultByType($dto->tenant_id, $dto->type, $id);
        }

        $saved = $this->priceListRepository->save($priceList);

        $productIds = $this->priceListItemRepository->getDistinctProductIdsByPriceList($saved->getTenantId(), $saved->getId());
        foreach ($productIds as $productId) {
            $this->refreshProjectionService->execute($saved->getTenantId(), $productId);
        }

        return $saved;
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
