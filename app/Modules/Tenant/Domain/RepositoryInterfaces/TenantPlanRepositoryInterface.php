<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantPlan;

interface TenantPlanRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?TenantPlan;

    public function getActive(?string $billingInterval = null): Collection;

    public function save(TenantPlan $plan): TenantPlan;
}
