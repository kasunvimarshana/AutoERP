<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Domain\Entities\UserDevice;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;

class FindUserDevicesService implements FindUserDevicesServiceInterface
{
    public function __construct(
        private readonly UserDeviceRepositoryInterface $userDeviceRepository
    ) {}

    public function find(int $id): ?UserDevice
    {
        return $this->userDeviceRepository->find($id);
    }

    public function findByUserAndToken(int $userId, string $deviceToken): ?UserDevice
    {
        return $this->userDeviceRepository->findByUserAndToken($userId, $deviceToken);
    }

    public function paginateByUser(int $userId, ?string $platform, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->userDeviceRepository->paginateByUser($userId, $platform, $perPage, $page);
    }
}
