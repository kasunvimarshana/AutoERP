<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\Core\Contracts\PlatformPermissionDirectoryInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\PlatformOperatorPermissionModel;

final class PlatformOperatorAccessResolver implements
    PlatformOperatorCheckerInterface,
    PlatformPermissionCheckerInterface,
    PlatformPermissionDirectoryInterface
{
    public function __construct(
        private readonly PlatformOperatorModel $operators,
        private readonly PlatformOperatorPermissionModel $assignments,
    ) {}

    public function isPlatformOperator(int $operatorId): bool
    {
        return $this->isActive($operatorId);
    }

    public function allows(int $operatorId, string $permission): bool
    {
        $permission = trim($permission);
        if ($permission === '' || ! $this->isActive($operatorId)) {
            return false;
        }

        return $this->assignments->newQuery()
            ->where('platform_operator_id', $operatorId)
            ->whereHas('permission', static fn ($query) => $query
                ->where('name', $permission)
                ->where('is_active', true))
            ->exists();
    }

    /** @return list<string> */
    public function permissions(int $operatorId): array
    {
        if (! $this->isActive($operatorId)) {
            return [];
        }

        return $this->assignments->newQuery()
            ->where('platform_operator_id', $operatorId)
            ->whereHas('permission', static fn ($query) => $query->where('is_active', true))
            ->with('permission:id,name')
            ->get()
            ->map(static fn (PlatformOperatorPermissionModel $assignment): ?string => $assignment->permission?->name)
            ->filter(static fn (mixed $name): bool => is_string($name) && $name !== '')
            ->sort()
            ->values()
            ->all();
    }

    private function isActive(int $operatorId): bool
    {
        return $operatorId > 0
            && $this->operators->newQuery()
                ->whereKey($operatorId)
                ->where('status', PlatformOperatorStatus::ACTIVE)
                ->whereNotNull('credentials_ready_at')
                ->exists();
    }
}
