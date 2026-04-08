<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Tax\Application\Contracts\TaxGroupRateServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;

class TaxGroupRateService implements TaxGroupRateServiceInterface
{
    public function __construct(
        private readonly TaxGroupRateRepositoryInterface $taxGroupRateRepository,
    ) {}

    public function getTaxGroupRate(string $tenantId, string $id): TaxGroupRate
    {
        $rate = $this->taxGroupRateRepository->findById($tenantId, $id);

        if ($rate === null) {
            throw new NotFoundException("TaxGroupRate [{$id}] not found.");
        }

        return $rate;
    }

    public function createTaxGroupRate(string $tenantId, array $data): TaxGroupRate
    {
        return DB::transaction(function () use ($tenantId, $data): TaxGroupRate {
            $now = now();
            $rate = new TaxGroupRate(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                taxGroupId: $data['tax_group_id'],
                name: $data['name'],
                rate: (float) $data['rate'],
                type: $data['type'] ?? 'percentage',
                sequence: (int) ($data['sequence'] ?? 0),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->taxGroupRateRepository->save($rate);

            return $rate;
        });
    }

    public function updateTaxGroupRate(string $tenantId, string $id, array $data): TaxGroupRate
    {
        return DB::transaction(function () use ($tenantId, $id, $data): TaxGroupRate {
            $existing = $this->getTaxGroupRate($tenantId, $id);

            $updated = new TaxGroupRate(
                id: $existing->id,
                tenantId: $existing->tenantId,
                taxGroupId: $existing->taxGroupId,
                name: $data['name'] ?? $existing->name,
                rate: (float) ($data['rate'] ?? $existing->rate),
                type: $data['type'] ?? $existing->type,
                sequence: (int) ($data['sequence'] ?? $existing->sequence),
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->taxGroupRateRepository->save($updated);

            return $updated;
        });
    }

    public function deleteTaxGroupRate(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getTaxGroupRate($tenantId, $id);
            $this->taxGroupRateRepository->delete($tenantId, $id);
        });
    }

    public function getRatesForGroup(string $tenantId, string $taxGroupId): array
    {
        return $this->taxGroupRateRepository->findByTaxGroup($tenantId, $taxGroupId);
    }
}
