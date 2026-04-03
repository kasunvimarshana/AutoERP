<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Pricing\Application\Contracts\CreatePriceListItemServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListItemData;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Events\PriceListItemCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class CreatePriceListItemService extends BaseService implements CreatePriceListItemServiceInterface
{
    public function __construct(private readonly PriceListItemRepositoryInterface $priceListItemRepository)
    {
        parent::__construct($priceListItemRepository);
    }

    protected function handle(array $data): PriceListItem
    {
        $dto = PriceListItemData::fromArray($data);

        $priceListItem = new PriceListItem(
            tenantId:       $dto->tenantId,
            priceListId:    $dto->priceListId,
            productId:      $dto->productId,
            unitPrice:      $dto->unitPrice,
            minQuantity:    $dto->minQuantity,
            currencyCode:   $dto->currencyCode,
            variationId:    $dto->variationId,
            maxQuantity:    $dto->maxQuantity,
            discountPercent:$dto->discountPercent,
            markupPercent:  $dto->markupPercent,
            uomCode:        $dto->uomCode,
            isActive:       $dto->isActive,
            metadata:       $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->priceListItemRepository->save($priceListItem);
        $this->addEvent(new PriceListItemCreated($saved));

        return $saved;
    }
}
