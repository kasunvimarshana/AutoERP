<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Taxation\Domain\Entities\TaxRule;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;

class EloquentTaxRuleRepository extends EloquentRepository implements TaxRuleRepositoryInterface
{
    public function __construct(TaxRuleModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TaxRuleModel $m): TaxRule => $this->mapModelToDomainEntity($m));
    }

    public function save(TaxRule $taxRule): TaxRule
    {
        $savedModel = null;

        DB::transaction(function () use ($taxRule, &$savedModel) {
            $data = [
                'tenant_id'   => $taxRule->getTenantId(),
                'name'        => $taxRule->getName(),
                'tax_rate_id' => $taxRule->getTaxRateId(),
                'entity_type' => $taxRule->getEntityType(),
                'entity_id'   => $taxRule->getEntityId(),
                'jurisdiction'=> $taxRule->getJurisdiction(),
                'priority'    => $taxRule->getPriority(),
                'is_active'   => $taxRule->isActive(),
                'description' => $taxRule->getDescription(),
                'metadata'    => $taxRule->getMetadata()->toArray(),
            ];

            if ($taxRule->getId()) {
                $savedModel = $this->update($taxRule->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (!$savedModel instanceof TaxRuleModel) {
            throw new \RuntimeException('Failed to save TaxRule.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByTaxRate(int $taxRateId): Collection
    {
        return $this->model
            ->where('tax_rate_id', $taxRateId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByEntity(int $tenantId, string $entityType, ?int $entityId): Collection
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType);

        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }

        return $query->get()->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(TaxRuleModel $model): TaxRule
    {
        return new TaxRule(
            tenantId: $model->tenant_id,
            name: $model->name,
            taxRateId: $model->tax_rate_id,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            jurisdiction: $model->jurisdiction,
            priority: (int) $model->priority,
            isActive: (bool) $model->is_active,
            description: $model->description,
            metadata: isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
