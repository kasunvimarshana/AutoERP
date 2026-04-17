<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Domain\Entities\UserDevice;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserDeviceRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpsertUserDeviceService extends BaseService implements UpsertUserDeviceServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserDeviceRepositoryInterface $userDeviceRepository
    ) {
        parent::__construct($userDeviceRepository);
    }

    protected function handle(array $data): UserDevice
    {
        $userId = (int) $data['user_id'];
        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $deviceToken = (string) $data['device_token'];
        $existing = $this->userDeviceRepository->findByUserAndToken($userId, $deviceToken);
        $platform = array_key_exists('platform', $data)
            ? (is_string($data['platform']) ? $data['platform'] : null)
            : $existing?->getPlatform();
        $deviceName = array_key_exists('device_name', $data)
            ? (is_string($data['device_name']) ? $data['device_name'] : null)
            : $existing?->getDeviceName();
        $lastActiveAt = $this->resolveLastActiveAt($data['last_active_at'] ?? null) ?? new DateTimeImmutable;

        $device = new UserDevice(
            userId: $userId,
            deviceToken: $deviceToken,
            platform: $platform,
            deviceName: $deviceName,
            lastActiveAt: $lastActiveAt,
            id: $existing?->getId(),
            createdAt: $existing?->getCreatedAt()
        );

        return $this->userDeviceRepository->save($device);
    }

    private function resolveLastActiveAt(mixed $value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value)) {
            return new DateTimeImmutable($value);
        }

        return null;
    }
}
