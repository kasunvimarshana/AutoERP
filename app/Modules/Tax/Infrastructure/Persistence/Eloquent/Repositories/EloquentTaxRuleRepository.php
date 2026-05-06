<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tax\Domain\Entities\TaxRule;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxRuleModel;

class EloquentTaxRuleRepository extends EloquentRepository implements TaxRuleRepositoryInterface
{
    public function __construct(TaxRuleModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TaxRuleModel $model): TaxRule => $this->mapModelToDomainEntity($model));
    }

    public function save(TaxRule $taxRule): TaxRule
    {
        $data = [
            'tenant_id' => $taxRule->getTenantId(),
            'tax_group_id' => $taxRule->getTaxGroupId(),
            'product_category_id' => $taxRule->getProductCategoryId(),
            'party_type' => $taxRule->getPartyType(),
            'region' => $taxRule->getRegion(),
            'priority' => $taxRule->getPriority(),
            'row_version' => $taxRule->getRowVersion(),
        ];

        if ($taxRule->getId()) {
            $model = $this->update($taxRule->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TaxRuleModel $model */

        return $this->toDomainEntity($model);
    }

    public function findBestMatch(int $tenantId, ?int $productCategoryId, ?string $partyType, ?string $region): ?TaxRule
    {
        /** @var Collection<int, TaxRuleModel> $candidates */
        $candidates = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $filtered = $candidates
            ->filter(function (TaxRuleModel $rule) use ($productCategoryId, $partyType, $region): bool {
                if ($rule->product_category_id !== null && (int) $rule->product_category_id !== $productCategoryId) {
                    return false;
                }

                if ($rule->party_type !== null && (string) $rule->party_type !== $partyType) {
                    return false;
                }

                if ($rule->region !== null && strcasecmp((string) $rule->region, (string) $region) !== 0) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (TaxRuleModel $rule): int => $this->score($rule));

        $selected = $filtered->first();

        return $selected ? $this->toDomainEntity($selected) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?TaxRule
    {
        return parent::find($id, $columns);
    }

    private function score(TaxRuleModel $rule): int
    {
        $score = 0;

        if ($rule->product_category_id !== null) {
            $score++;
        }

        if ($rule->party_type !== null) {
            $score++;
        }

        if ($rule->region !== null) {
            $score++;
        }

        return $score;
    }

    private function mapModelToDomainEntity(TaxRuleModel $model): TaxRule
    {
        return new TaxRule(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            taxGroupId: (int) $model->tax_group_id,
            productCategoryId: $model->product_category_id !== null ? (int) $model->product_category_id : null,
            partyType: $model->party_type !== null ? (string) $model->party_type : null,
            region: $model->region !== null ? (string) $model->region : null,
            priority: (int) $model->priority,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
