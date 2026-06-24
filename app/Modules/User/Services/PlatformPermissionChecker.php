<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\User\Models\PlatformOperatorPermissionModel;
use Modules\User\Models\UserModel;

final class PlatformPermissionChecker implements PlatformPermissionCheckerInterface
{
    public function __construct(
        private readonly UserModel $users,
        private readonly PlatformOperatorPermissionModel $assignments,
    ) {}

    public function hasPermission(int $userId, string $permission): bool
    {
        $permission = strtolower(trim($permission));
        if ($userId < 1 || $permission === '') {
            return false;
        }

        return $this->isActivePlatformOperator($userId)
            && $this->assignments->newQuery()
                ->where('user_id', $userId)
                ->whereHas('permission', fn ($query) => $query
                    ->where('name', $permission)
                    ->where('is_active', true))
                ->exists();
    }

    public function permissions(int $userId): array
    {
        if ($userId < 1 || ! $this->isActivePlatformOperator($userId)) {
            return [];
        }

        return $this->assignments->newQuery()
            ->where('user_id', $userId)
            ->whereHas('permission', fn ($query) => $query->where('is_active', true))
            ->with('permission:id,name')
            ->get()
            ->map(fn (PlatformOperatorPermissionModel $assignment): string => (string) $assignment->permission?->name)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function isActivePlatformOperator(int $userId): bool
    {
        return $this->users->newQuery()
            ->whereKey($userId)
            ->whereNull('tenant_id')
            ->where('is_platform_operator', true)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists();
    }
}
