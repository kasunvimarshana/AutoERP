<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\UserDevice;

interface UserDeviceRepositoryInterface extends RepositoryInterface
{
    public function findByUserAndToken(int $userId, string $deviceToken): ?UserDevice;

    public function paginateByUser(int $userId, ?string $platform, int $perPage, int $page): LengthAwarePaginator;

    public function save(UserDevice $device): UserDevice;
}
