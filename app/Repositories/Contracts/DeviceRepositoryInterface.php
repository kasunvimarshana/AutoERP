<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Models\Device;
use Illuminate\Database\Eloquent\Collection;

interface DeviceRepositoryInterface
{
    public function findByDeviceId(string $deviceId): ?Device;

    public function findByUserId(int $userId): Collection;

    public function createOrUpdate(int $userId, string $deviceId, array $data): Device;

    public function updateLastActive(string $deviceId): void;

    public function deleteByUserId(int $userId): void;

    public function deleteByDeviceId(string $deviceId): void;
}
