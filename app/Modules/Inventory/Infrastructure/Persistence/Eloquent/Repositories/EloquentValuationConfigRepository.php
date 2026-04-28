<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;

class EloquentValuationConfigRepository implements ValuationConfigRepositoryInterface
{
    public function __construct(private readonly ValuationConfigModel $model) {}

    public function create(ValuationConfig $config): ValuationConfig
    {
        /** @var ValuationConfigModel $saved */
        $saved = $this->model->newQuery()->create($this->toArray($config));

        return $this->mapToEntity($saved);
    }

    public function update(ValuationConfig $config): ValuationConfig
    {
        if ($config->getId() === null) {
            return $config;
        }

        $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $config->getTenantId())
            ->where('id', $config->getId())
            ->update(array_merge($this->toArray($config), ['updated_at' => now()]));

        return $config;
    }

    public function delete(int $tenantId, int $id): void
    {
        $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    public function findById(int $tenantId, int $id): ?ValuationConfig
    {
        /** @var ValuationConfigModel|null $model */
        $model = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    /**
     * Resolve the effective config using scope precedence:
     *   product_id > warehouse_id > org_unit_id > tenant-only
     */
    public function resolveEffective(
        int $tenantId,
        ?int $productId = null,
        ?int $warehouseId = null,
        ?int $orgUnitId = null,
        ?string $transactionType = null,
    ): ?ValuationConfig {
        /** @var Collection<int, ValuationConfigModel> $candidates */
        $candidates = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $scopePriority = [
            fn (ValuationConfigModel $c) => $productId !== null && $c->product_id !== null && (int) $c->product_id === $productId ? 4 : null,
            fn (ValuationConfigModel $c) => $warehouseId !== null && $c->warehouse_id !== null && (int) $c->warehouse_id === $warehouseId ? 3 : null,
            fn (ValuationConfigModel $c) => $orgUnitId !== null && $c->org_unit_id !== null && (int) $c->org_unit_id === $orgUnitId ? 2 : null,
            fn (ValuationConfigModel $c) => ($c->product_id === null && $c->warehouse_id === null && $c->org_unit_id === null) ? 1 : null,
        ];

        /** @var ValuationConfigModel|null $best */
        $best = null;
        $bestPriority = 0;

        foreach ($candidates as $candidate) {
            if ($transactionType !== null && $candidate->transaction_type !== null && $candidate->transaction_type !== $transactionType) {
                continue;
            }

            foreach ($scopePriority as $checker) {
                $priority = $checker($candidate);
                if ($priority !== null && $priority > $bestPriority) {
                    $bestPriority = $priority;
                    $best = $candidate;
                }
            }
        }

        return $best !== null ? $this->mapToEntity($best) : null;
    }

    public function paginate(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function toArray(ValuationConfig $config): array
    {
        return [
            'tenant_id' => $config->getTenantId(),
            'org_unit_id' => $config->getOrgUnitId(),
            'warehouse_id' => $config->getWarehouseId(),
            'product_id' => $config->getProductId(),
            'transaction_type' => $config->getTransactionType(),
            'valuation_method' => $config->getValuationMethod(),
            'allocation_strategy' => $config->getAllocationStrategy(),
            'is_active' => $config->isActive(),
            'metadata' => $config->getMetadata(),
        ];
    }

    private function mapToEntity(ValuationConfigModel $model): ValuationConfig
    {
        return new ValuationConfig(
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            warehouseId: $model->warehouse_id !== null ? (int) $model->warehouse_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            transactionType: $model->transaction_type,
            valuationMethod: (string) $model->valuation_method,
            allocationStrategy: (string) $model->allocation_strategy,
            isActive: (bool) $model->is_active,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
        );
    }
}
