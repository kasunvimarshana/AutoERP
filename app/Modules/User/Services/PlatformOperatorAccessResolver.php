<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Models\PlatformOperatorModel;

final class PlatformOperatorAccessResolver implements PlatformOperatorCheckerInterface
{
    public function __construct(private readonly PlatformOperatorModel $operators) {}

    public function isPlatformOperator(int $userId): bool
    {
        return $userId > 0 && $this->operators->newQuery()
            ->whereKey($userId)
            ->where('status', PlatformOperatorStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')
            ->exists();
    }
}
