<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\User\Domain\Exceptions\UserDeviceNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;

class DeleteUserDeviceService extends BaseService implements DeleteUserDeviceServiceInterface
{
    public function __construct(
        private readonly UserDeviceRepositoryInterface $userDeviceRepository
    ) {
        parent::__construct($userDeviceRepository);
    }

    protected function handle(array $data): bool
    {
        $deviceId = (int) $data['device_id'];
        $userId = (int) $data['user_id'];

        $device = $this->userDeviceRepository->find($deviceId);
        if (! $device || $device->getUserId() !== $userId) {
            throw new UserDeviceNotFoundException($deviceId);
        }

        return $this->userDeviceRepository->delete($deviceId);
    }
}
