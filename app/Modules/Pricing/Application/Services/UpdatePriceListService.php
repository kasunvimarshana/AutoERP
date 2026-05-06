<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Pricing\Application\Contracts\UpdatePriceListServiceInterface;
use Modules\Pricing\Application\DTOs\PriceListData;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Exceptions\PriceListNotFoundException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class UpdatePriceListService extends BaseService implements UpdatePriceListServiceInterface
{
    public function __construct(private readonly PriceListRepositoryInterface $priceListRepository)
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


        if ($dto->rowVersion !== $priceList->getRowVersion()) {
            throw new ConcurrentModificationException('PriceList', $id);
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

        return $this->priceListRepository->save($priceList);
    }

    private function toDate(?string $value): ?\DateTimeInterface
    {
        return $value !== null ? new \DateTimeImmutable($value) : null;
    }
}
