<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Tax\Application\Contracts\TaxGroupServiceInterface;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\Events\TaxGroupCreated;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class TaxGroupService implements TaxGroupServiceInterface
{
    public function __construct(
        private readonly TaxGroupRepositoryInterface $taxGroupRepository,
    ) {}

    public function getTaxGroup(string $tenantId, string $id): TaxGroup
    {
        $group = $this->taxGroupRepository->findById($tenantId, $id);

        if ($group === null) {
            throw new NotFoundException("TaxGroup [{$id}] not found.");
        }

        return $group;
    }

    public function createTaxGroup(string $tenantId, array $data): TaxGroup
    {
        return DB::transaction(function () use ($tenantId, $data): TaxGroup {
            $now = now();
            $group = new TaxGroup(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                name: $data['name'],
                code: $data['code'],
                description: $data['description'] ?? null,
                isCompound: (bool) ($data['is_compound'] ?? false),
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->taxGroupRepository->save($group);

            Event::dispatch(new TaxGroupCreated($group));

            return $group;
        });
    }

    public function updateTaxGroup(string $tenantId, string $id, array $data): TaxGroup
    {
        return DB::transaction(function () use ($tenantId, $id, $data): TaxGroup {
            $existing = $this->getTaxGroup($tenantId, $id);

            $updated = new TaxGroup(
                id: $existing->id,
                tenantId: $existing->tenantId,
                name: $data['name'] ?? $existing->name,
                code: $data['code'] ?? $existing->code,
                description: $data['description'] ?? $existing->description,
                isCompound: (bool) ($data['is_compound'] ?? $existing->isCompound),
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->taxGroupRepository->save($updated);

            return $updated;
        });
    }

    public function deleteTaxGroup(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getTaxGroup($tenantId, $id);
            $this->taxGroupRepository->delete($tenantId, $id);
        });
    }

    public function getAllTaxGroups(string $tenantId): array
    {
        return $this->taxGroupRepository->findAll($tenantId);
    }
}
