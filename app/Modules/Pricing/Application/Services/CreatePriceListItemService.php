<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Product\Application\Contracts\RefreshProductSearchProjectionServiceInterface;

class CreatePriceListItemService extends BaseService implements CreatePriceListItemServiceInterface
{
    public function __construct(
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
        private readonly PriceListRepositoryInterface $priceListRepository,
        private readonly RefreshProductSearchProjectionServiceInterface $refreshProjectionService,
    ) {
        parent::__construct($priceListItemRepository);
    }

    protected function handle(array $data): PriceListItem
    {
        $dto = PriceListItemData::fromArray($data);

        $priceList = $this->priceListRepository->find($dto->price_list_id);
        if (! $priceList) {
            throw new PriceListNotFoundException($dto->price_list_id);
        }

        $item = new PriceListItem(
            tenantId: $priceList->getTenantId(),
            priceListId: $dto->price_list_id,
            productId: $dto->product_id,
            uomId: $dto->uom_id,
            price: $dto->price,
            variantId: $dto->variant_id,
            minQuantity: $dto->min_quantity,
            discountPct: $dto->discount_pct,
            validFrom: $this->toDate($dto->valid_from),
            validTo: $this->toDate($dto->valid_to),
        );

        $saved = $this->priceListItemRepository->save($item);
        $this->refreshProjectionService->execute($saved->getTenantId(), $saved->getProductId());

        return $saved;
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
