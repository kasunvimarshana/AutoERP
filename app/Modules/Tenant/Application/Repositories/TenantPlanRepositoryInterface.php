<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface TenantPlanRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name, array $with = []): ?Model;

    public function getActive(array $with = []): Collection;

    public function getInactive(array $with = []): Collection;
}
