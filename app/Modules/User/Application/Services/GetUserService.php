<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\User\Application\Contracts\GetUserServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class GetUserService implements GetUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function findById(int $id): User
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        return $user;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }
}
