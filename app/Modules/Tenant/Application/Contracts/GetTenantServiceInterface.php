<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Tenant\Domain\Entities\Tenant;

interface GetTenantServiceInterface
{
    public function findById(int $id): Tenant;
    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator;
}
