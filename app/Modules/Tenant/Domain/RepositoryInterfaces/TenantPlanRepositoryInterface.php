<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantPlan;

interface TenantPlanRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?TenantPlan;

    /**
     * @return iterable<int, TenantPlan>
     */
    public function getActive(?string $billingInterval = null): iterable;

    public function save(TenantPlan $plan): TenantPlan;
}
