<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Pricing\Application\Contracts\PriceListServiceInterface;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Events\PriceListCreated;
use Modules\Pricing\Domain\Events\PriceListUpdated;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;

class PriceListService implements PriceListServiceInterface
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceListRepository,
    ) {}

    public function getPriceList(string $tenantId, string $id): PriceList
    {
        $priceList = $this->priceListRepository->findById($tenantId, $id);
        if ($priceList === null) {
            throw new NotFoundException('PriceList', $id);
        }
        return $priceList;
    }

    public function getAllPriceLists(string $tenantId): array
    {
        return $this->priceListRepository->findAll($tenantId);
    }

    public function createPriceList(string $tenantId, array $data): PriceList
    {
        return DB::transaction(function () use ($tenantId, $data): PriceList {
            $now = now();
            $priceList = new PriceList(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                currency: $data['currency'] ?? 'USD',
                isDefault: (bool) ($data['is_default'] ?? false),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );
            $this->priceListRepository->save($priceList);
            Event::dispatch(new PriceListCreated($priceList));
            return $priceList;
        });
    }

    public function updatePriceList(string $tenantId, string $id, array $data): PriceList
    {
        return DB::transaction(function () use ($tenantId, $id, $data): PriceList {
            $existing = $this->getPriceList($tenantId, $id);
            $priceList = new PriceList(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                currency: $data['currency'] ?? $existing->currency,
                isDefault: isset($data['is_default']) ? (bool) $data['is_default'] : $existing->isDefault,
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );
            $this->priceListRepository->save($priceList);
            Event::dispatch(new PriceListUpdated($priceList));
            return $priceList;
        });
    }

    public function deletePriceList(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getPriceList($tenantId, $id);
            $this->priceListRepository->delete($tenantId, $id);
        });
    }
}
