<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\UserDevice;
use Modules\Core\Repositories\BaseRepository;

/**
 * UserDevice Repository
 *
 * Handles user device tracking operations
 */
class UserDeviceRepository extends BaseRepository
{
    /**
     * Make a new UserDevice model instance
     */
    protected function makeModel(): Model
    {
        return new UserDevice;
    }

    /**
     * Find device by device ID
     */
    public function findByDeviceId(string $deviceId, string $userId, string $tenantId): ?UserDevice
    {
        return $this->model
            ->where('device_id', $deviceId)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find or create device
     */
    public function findOrCreateDevice(array $deviceData): UserDevice
    {
        return $this->firstOrCreate(
            [
                'device_id' => $deviceData['device_id'],
                'user_id' => $deviceData['user_id'],
                'tenant_id' => $deviceData['tenant_id'],
            ],
            $deviceData
        );
    }

    /**
     * Get all devices for user
     */
    public function getByUser(string $userId, string $tenantId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get active devices for user
     */
    public function getActiveDevices(string $userId, string $tenantId, int $daysActive = 30): Collection
    {
        $cutoffDate = Carbon::now()->subDays($daysActive);

        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('last_used_at', '>=', $cutoffDate)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Get inactive devices for user
     */
    public function getInactiveDevices(string $userId, string $tenantId, int $daysInactive = 30): Collection
    {
        $cutoffDate = Carbon::now()->subDays($daysInactive);

        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_used_at', '<', $cutoffDate)
                    ->orWhereNull('last_used_at');
            })
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Mark device as used
     */
    public function markAsUsed(string $deviceId): bool
    {
        $device = $this->findOrFail($deviceId);

        return $device->update(['last_used_at' => now()]);
    }

    /**
     * Update device last used
     */
    public function updateLastUsed(string $deviceId, string $ipAddress): bool
    {
        $device = $this->findOrFail($deviceId);

        return $device->update([
            'last_used_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Count devices for user
     */
    public function countByUser(string $userId, string $tenantId): int
    {
        return $this->count([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Count active devices for user
     */
    public function countActiveDevices(string $userId, string $tenantId, int $daysActive = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysActive);

        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('last_used_at', '>=', $cutoffDate)
            ->count();
    }

    /**
     * Get devices by type
     */
    public function getByType(string $userId, string $tenantId, string $deviceType): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('device_type', $deviceType)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Delete device by device ID
     */
    public function deleteByDeviceId(string $deviceId, string $userId, string $tenantId): bool
    {
        $device = $this->findByDeviceId($deviceId, $userId, $tenantId);

        if (! $device) {
            return false;
        }

        return $device->delete();
    }

    /**
     * Delete all devices for user
     */
    public function deleteAllByUser(string $userId, string $tenantId): int
    {
        return $this->deleteBy([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Delete inactive devices
     */
    public function deleteInactiveDevices(string $userId, string $tenantId, int $daysInactive = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysInactive);

        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_used_at', '<', $cutoffDate)
                    ->orWhereNull('last_used_at');
            })
            ->delete();
    }

    /**
     * Update device metadata
     */
    public function updateMetadata(string $deviceId, array $metadata): bool
    {
        $device = $this->findOrFail($deviceId);
        $currentMetadata = $device->metadata ?? [];

        return $device->update(['metadata' => array_merge($currentMetadata, $metadata)]);
    }

    /**
     * Get most recent device
     */
    public function getMostRecent(string $userId, string $tenantId): ?UserDevice
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('last_used_at', 'desc')
            ->first();
    }

    /**
     * Get paginated devices for user
     */
    public function getPaginatedByUser(string $userId, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('last_used_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search devices by name or type
     */
    public function searchDevices(string $userId, string $tenantId, string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($term) {
                $query->where('device_name', 'like', "%{$term}%")
                    ->orWhere('device_type', 'like', "%{$term}%");
            })
            ->orderBy('last_used_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get devices by IP address
     */
    public function getByIpAddress(string $userId, string $tenantId, string $ipAddress): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('ip_address', $ipAddress)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Clean up old devices
     */
    public function cleanupOldDevices(int $daysOld = 180): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return $this->model
            ->where(function ($query) use ($cutoffDate) {
                $query->where('last_used_at', '<', $cutoffDate)
                    ->orWhereNull('last_used_at');
            })
            ->delete();
    }
}
