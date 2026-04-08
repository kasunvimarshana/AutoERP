<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class CreatePriceListService extends BaseService implements CreatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
    {
        parent::__construct($priceListRepository);
    }

    protected function handle(array $data): PriceList
    {
        $dto = PriceListData::fromArray($data);

        $priceList = new PriceList(
            tenantId:      $dto->tenantId,
            name:          $dto->name,
            code:          $dto->code,
            type:          $dto->type,
            pricingMethod: $dto->pricingMethod,
            currencyCode:  $dto->currencyCode,
            startDate:     $dto->startDate ? new \DateTimeImmutable($dto->startDate) : null,
            endDate:       $dto->endDate ? new \DateTimeImmutable($dto->endDate) : null,
            isActive:      $dto->isActive,
            description:   $dto->description,
            metadata:      $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->priceListRepository->save($priceList);
        $this->addEvent(new PriceListCreated($saved));

        return $saved;
    }
}
