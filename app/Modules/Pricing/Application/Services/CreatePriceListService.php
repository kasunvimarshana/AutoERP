<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\CreatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
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
            tenantId: $dto->tenant_id,
            name: $dto->name,
            type: $dto->type,
            currencyId: $dto->currency_id,
            isDefault: $dto->is_default,
            validFrom: $this->toDate($dto->valid_from),
            validTo: $this->toDate($dto->valid_to),
            isActive: $dto->is_active,
        );

        if ($dto->is_default) {
            $this->priceListRepository->clearDefaultByType($dto->tenant_id, $dto->type);
        }

        return $this->priceListRepository->save($priceList);
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
