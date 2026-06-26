<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\PlatformPermissionCheckerInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\PlatformOperatorPermissionModel;

final class PlatformPermissionChecker implements PlatformPermissionCheckerInterface
{
    public function __construct(
        private readonly PlatformOperatorModel $operators,
        private readonly PlatformOperatorPermissionModel $assignments,
    ) {}

    public function allows(int $operatorId, string $permission): bool
    {
        if ($operatorId < 1 || trim($permission) === '' || ! $this->isActive($operatorId)) {
            return false;
        }

        return $this->assignments->newQuery()
            ->where('platform_operator_id', $operatorId)
            ->whereHas('permission', fn ($query) => $query
                ->where('name', trim($permission))
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
            ->whereHas('permission', fn ($query) => $query->where('is_active', true))
            ->with('permission:id,name')
            ->get()
            ->map(fn (PlatformOperatorPermissionModel $assignment): ?string => $assignment->permission?->name)
            ->filter(static fn (mixed $name): bool => is_string($name) && $name !== '')
            ->sort()->values()->all();
    }

    private function isActive(int $operatorId): bool
    {
        return $this->operators->newQuery()
            ->whereKey($operatorId)
            ->where('status', PlatformOperatorStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')
            ->exists();
    }
}
