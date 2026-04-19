<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\UserDevice;

interface UserDeviceRepositoryInterface extends RepositoryInterface
{
    public function findByUserAndToken(int $userId, string $deviceToken): ?UserDevice;

    public function paginateByUser(int $userId, ?string $platform, int $perPage, int $page): mixed;

    public function save(UserDevice $device): UserDevice;
}
