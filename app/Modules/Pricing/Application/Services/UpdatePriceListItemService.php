<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Pricing\Application\Contracts\UpdatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\UpdatePriceListItemData;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Events\PriceListItemUpdated;
use Modules\Pricing\Domain\Exceptions\PriceListItemNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class UpdatePriceListItemService extends BaseService implements UpdatePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository)
    {
        parent::__construct($priceListItemRepository);
    }

    protected function handle(array $data): PriceListItem
    {
        $dto = UpdatePriceListItemData::fromArray($data);

        /** @var PriceListItem|null $priceListItem */
        $priceListItem = $this->priceListItemRepository->find($dto->id);
        if (! $priceListItem) {
            throw new PriceListItemNotFoundException($dto->id);
        }

        $priceListItem->updateDetails(
            productId:      $dto->productId ?? $priceListItem->getProductId(),
            unitPrice:      $dto->unitPrice ?? $priceListItem->getUnitPrice(),
            minQuantity:    $dto->minQuantity ?? $priceListItem->getMinQuantity(),
            currencyCode:   $dto->currencyCode ?? $priceListItem->getCurrencyCode(),
            variationId:    $dto->variationId ?? $priceListItem->getVariationId(),
            maxQuantity:    $dto->maxQuantity ?? $priceListItem->getMaxQuantity(),
            discountPercent:$dto->discountPercent ?? $priceListItem->getDiscountPercent(),
            markupPercent:  $dto->markupPercent ?? $priceListItem->getMarkupPercent(),
            uomCode:        $dto->uomCode ?? $priceListItem->getUomCode(),
            isActive:       $dto->isActive ?? $priceListItem->isActive(),
            metadata:       $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->priceListItemRepository->save($priceListItem);
        $this->addEvent(new PriceListItemUpdated($saved));

        return $saved;
    }
}
