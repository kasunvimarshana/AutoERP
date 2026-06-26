<?php

declare(strict_types=1);

namespace Modules\User\Services\Authentication;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Constants\UserStatus;
use Modules\User\Contracts\AuthenticationPrincipalProviderInterface;
use Modules\User\Models\PlatformOperatorModel;
use Modules\User\Models\UserModel;

final class AuthenticationPrincipalProvider implements AuthenticationPrincipalProviderInterface
{
    public function __construct(private readonly TenantExecutionContextInterface $executionContext) {}

    public function tenantPrincipal(int $tenantId, int $userId): ?Authenticatable
    {
        if ($tenantId < 1 || $userId < 1) {
            return null;
        }

        return $this->executionContext->runForTenant($tenantId, static fn (): ?UserModel => UserModel::query()
            ->whereKey($userId)
            ->where('tenant_id', $tenantId)
            ->where('status', UserStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')
            ->whereNull('deleted_at')
            ->first());
    }

    public function platformPrincipal(int $operatorId): ?Authenticatable
    {
        if ($operatorId < 1) {
            return null;
        }

        return $this->executionContext->runAsControlPlane(static fn (): ?PlatformOperatorModel => PlatformOperatorModel::query()
            ->whereKey($operatorId)
            ->where('status', PlatformOperatorStatus::ACTIVE)
            ->whereNotNull('credentials_ready_at')
            ->first());
    }
}
