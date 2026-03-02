<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use Modules\Pricing\Domain\Entities\DiscountRule;
use Modules\Pricing\Domain\Entities\PriceList;

/**
 * Pricing repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant.
 */
class PricingRepository extends AbstractRepository implements PricingRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = PriceList::class;
    }

    /**
     * {@inheritdoc}
     */
    public function allDiscountRules(): Collection
    {
        return DiscountRule::query()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function createDiscountRule(array $data): Model
    {
        return DiscountRule::query()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function findDiscountRuleOrFail(int|string $id): Model
    {
        return DiscountRule::query()->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscountRule(int|string $id, array $data): Model
    {
        $rule = $this->findDiscountRuleOrFail($id);
        $rule->update($data);

        return $rule->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDiscountRule(int|string $id): bool
    {
        $rule = $this->findDiscountRuleOrFail($id);

        return (bool) $rule->delete();
    }
}
