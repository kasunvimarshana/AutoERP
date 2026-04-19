<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantPlanModel;

class EloquentTenantPlanRepository extends EloquentRepository implements TenantPlanRepositoryInterface
{
    public function __construct(TenantPlanModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TenantPlanModel $model): TenantPlan => $this->mapModelToDomainEntity($model));
    }

    public function findBySlug(string $slug): ?TenantPlan
    {
        $model = $this->model->where('slug', $slug)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getActive(?string $billingInterval = null): Collection
    {
        $query = $this->model->where('is_active', true);

        if ($billingInterval !== null && $billingInterval !== '') {
            $query->where('billing_interval', $billingInterval);
        }

        return $this->toDomainCollection($query->get());
    }

    public function save(TenantPlan $plan): TenantPlan
    {
        $data = [
            'name' => $plan->getName(),
            'slug' => $plan->getSlug(),
            'features' => $plan->getFeatures(),
            'limits' => $plan->getLimits(),
            'price' => $plan->getPrice(),
            'currency_code' => $plan->getCurrencyCode(),
            'billing_interval' => $plan->getBillingInterval(),
            'is_active' => $plan->isActive(),
        ];

        if ($plan->getId()) {
            $model = $this->update($plan->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TenantPlanModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): ?TenantPlan
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TenantPlanModel $model): TenantPlan
    {
        return new TenantPlan(
            name: $model->name,
            slug: $model->slug,
            features: $model->features,
            limits: $model->limits,
            price: (string) $model->price,
            currencyCode: $model->currency_code,
            billingInterval: $model->billing_interval,
            isActive: (bool) $model->is_active,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
