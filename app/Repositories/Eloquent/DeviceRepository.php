<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Domain\Models\Device;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class DeviceRepository implements DeviceRepositoryInterface
{
    public function findByDeviceId(string $deviceId): ?Device
    {
        return Device::where('device_id', $deviceId)->first();
    }

    public function findByUserId(int $userId): Collection
    {
        return Device::where('user_id', $userId)->get();
    }

    public function createOrUpdate(int $userId, string $deviceId, array $data): Device
    {
        return Device::updateOrCreate(
            ['device_id' => $deviceId],
            array_merge($data, ['user_id' => $userId, 'device_id' => $deviceId])
        );
    }

    public function updateLastActive(string $deviceId): void
    {
        Device::where('device_id', $deviceId)->update([
            'last_active_at' => now(),
        ]);
    }

    public function deleteByUserId(int $userId): void
    {
        Device::where('user_id', $userId)->delete();
    }

    public function deleteByDeviceId(string $deviceId): void
    {
        Device::where('device_id', $deviceId)->delete();
    }
}
