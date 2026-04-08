<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\UpdatePriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListUpdated;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class UpdatePriceListService extends BaseService implements UpdatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): PriceList
    {
        $dto = UpdatePriceListData::fromArray($data);

        /** @var PriceList|null $priceList */
        $priceList = $this->priceListRepository->find($dto->id);
        if (! $priceList) {
            throw new PriceListNotFoundException($dto->id);
        }

        $priceList->updateDetails(
            name:          $dto->name ?? $priceList->getName(),
            code:          $dto->code ?? $priceList->getCode(),
            type:          $dto->type ?? $priceList->getType(),
            pricingMethod: $dto->pricingMethod ?? $priceList->getPricingMethod(),
            currencyCode:  $dto->currencyCode ?? $priceList->getCurrencyCode(),
            startDate:     $dto->startDate ? new \DateTimeImmutable($dto->startDate) : $priceList->getStartDate(),
            endDate:       $dto->endDate ? new \DateTimeImmutable($dto->endDate) : $priceList->getEndDate(),
            isActive:      $dto->isActive ?? $priceList->isActive(),
            description:   $dto->description ?? $priceList->getDescription(),
            metadata:      $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->priceListRepository->save($priceList);
        $this->addEvent(new PriceListUpdated($saved));

        return $saved;
    }
}
