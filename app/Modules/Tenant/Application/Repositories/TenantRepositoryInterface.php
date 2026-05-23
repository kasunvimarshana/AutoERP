<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name, array $with = []): ?Model;

    public function getByStatus(string $status, array $with = []): Collection;

    public function paginateByStatus(string $status, int $perPage = 15, array $with = []): LengthAwarePaginator;
}
