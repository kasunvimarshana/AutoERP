<?php

declare(strict_types=1);

namespace Modules\User\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\User\Domain\Entities\UserDevice;

interface FindUserDevicesServiceInterface
{
    public function find(int $id): ?UserDevice;

    public function findByUserAndToken(int $userId, string $deviceToken): ?UserDevice;

    public function paginateByUser(int $userId, ?string $platform, int $perPage, int $page): LengthAwarePaginator;
}
