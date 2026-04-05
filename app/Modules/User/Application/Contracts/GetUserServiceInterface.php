<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\User\Domain\Entities\User;

interface GetUserServiceInterface
{
    public function findById(int $id): User;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
}
