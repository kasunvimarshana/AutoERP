<?php

declare(strict_types=1);

namespace Modules\Auth\Services\OrganizationUnit;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Enums\SessionStatus;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\OrganizationUnitLifecycleBlockerInterface;

final readonly class AuthOrganizationUnitLifecycleBlocker implements OrganizationUnitLifecycleBlockerInterface
{
    public function __construct(private ClockInterface $clock) {}

    public function blockers(int $tenantId, int $organizationUnitId): array
    {
        $sessions = DB::table('auth_sessions')
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('status', SessionStatus::ACTIVE->value)
            ->where('expires_at', '>', $this->clock->now())
            ->count();

        return $sessions > 0 ? [[
            'code' => 'ACTIVE_AUTH_SESSIONS',
            'message' => 'Switch or revoke active sessions before changing this organization unit lifecycle.',
            'count' => $sessions,
        ]] : [];
    }
}
