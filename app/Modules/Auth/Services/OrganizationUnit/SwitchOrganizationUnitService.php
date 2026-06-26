<?php

declare(strict_types=1);

namespace Modules\Auth\Services\OrganizationUnit;

use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Constants\AuthStatus;
use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\DTOs\DataRecord;

final class SwitchOrganizationUnitService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly OrganizationUnitUserAccessCheckerInterface $access,
        private readonly OrganizationUnitDirectoryInterface $organizationUnits,
        private readonly AuditRecorderInterface $audit,
    ) {}

    public function execute(int $targetOrganizationUnitId): DataRecord
    {
        $user = $this->currentUser->requireCurrent();
        $tenantId = $this->currentTenant->requireCurrent()->tenantId();
        $tokenPayload = $user->tokenPayload();
        $accessTokenId = $this->positiveInt($tokenPayload['id'] ?? null);
        $sessionId = $this->positiveInt($tokenPayload['session_id'] ?? null);
        if ($accessTokenId === null) {
            throw new DomainException('The authenticated access token cannot be updated. Sign in again.');
        }

        return DB::transaction(function () use (
            $tenantId,
            $user,
            $targetOrganizationUnitId,
            $accessTokenId,
            $sessionId,
        ): DataRecord {
            $this->organizationUnits->assertActive($tenantId, [$targetOrganizationUnitId], true);
            if (! $this->access->canAccessOrganizationUnit(
                $user->userId(),
                $tenantId,
                $targetOrganizationUnitId,
                true,
            )) {
                throw new DomainException('You do not have an active assignment to the selected organization unit.');
            }

            $token = AuthAccessTokenModel::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->userId())
                ->whereKey($accessTokenId)
                ->where('status', AuthStatus::ACTIVE)
                ->lockForUpdate()
                ->first();
            if (! $token instanceof AuthAccessTokenModel) {
                throw new DomainException('The authenticated access token is no longer active. Sign in again.');
            }

            $previousOrganizationUnitId = $this->positiveInt($token->getAttribute('organization_unit_id'));

            if ($sessionId !== null) {
                AuthSessionModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->userId())
                    ->whereKey($sessionId)
                    ->where('status', AuthStatus::ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (AuthSessionModel $session) use ($targetOrganizationUnitId): void {
                        $session->forceFill([
                            'organization_unit_id' => $targetOrganizationUnitId,
                            'row_version' => (int) $session->getAttribute('row_version') + 1,
                        ])->save();
                    });

                AuthAccessTokenModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->userId())
                    ->where('session_id', $sessionId)
                    ->where('status', AuthStatus::ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (AuthAccessTokenModel $activeToken) use ($targetOrganizationUnitId): void {
                        $activeToken->forceFill([
                            'organization_unit_id' => $targetOrganizationUnitId,
                            'row_version' => (int) $activeToken->getAttribute('row_version') + 1,
                        ])->save();
                    });

                AuthRefreshTokenModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->userId())
                    ->where('session_id', $sessionId)
                    ->where('status', AuthStatus::ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (AuthRefreshTokenModel $refreshToken) use ($targetOrganizationUnitId): void {
                        $refreshToken->forceFill([
                            'organization_unit_id' => $targetOrganizationUnitId,
                            'row_version' => (int) $refreshToken->getAttribute('row_version') + 1,
                        ])->save();
                    });
            } else {
                $token->forceFill([
                    'organization_unit_id' => $targetOrganizationUnitId,
                    'row_version' => (int) $token->getAttribute('row_version') + 1,
                ])->save();
                AuthRefreshTokenModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->userId())
                    ->where('access_token_id', $accessTokenId)
                    ->where('status', AuthStatus::ACTIVE)
                    ->update([
                        'organization_unit_id' => $targetOrganizationUnitId,
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_at' => now(),
                    ]);
            }

            $this->audit->record(new AuditEventData(
                eventName: 'auth.organization_unit.switched',
                eventCategory: AuditEventCategory::AUTHORIZATION,
                sourceModule: 'auth',
                subjectType: 'auth_session',
                subjectId: (string) ($sessionId ?? $accessTokenId),
                subjectReference: 'Organization-unit context switch',
                changes: [
                    'before' => ['organization_unit_id' => $previousOrganizationUnitId],
                    'after' => ['organization_unit_id' => $targetOrganizationUnitId],
                ],
                metadata: [
                    'user_id' => $user->userId(),
                    'session_id' => $sessionId,
                    'access_token_id' => $accessTokenId,
                ],
                tags: ['authentication', 'organization-unit', 'scope-switch'],
            ));

            $unit = $this->organizationUnits->summaries($tenantId, [$targetOrganizationUnitId])[$targetOrganizationUnitId] ?? null;
            if ($unit === null) {
                throw new DomainException('The selected organization unit is no longer available.');
            }

            return new DataRecord([
                ...$unit,
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]);
        }, 3);
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
