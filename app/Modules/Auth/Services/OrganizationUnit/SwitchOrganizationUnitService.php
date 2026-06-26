<?php

declare(strict_types=1);

namespace Modules\Auth\Services\OrganizationUnit;

use Illuminate\Database\DatabaseManager;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Enums\SessionStatus;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Models\AuthSessionModel;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final readonly class SwitchOrganizationUnitService
{
    public function __construct(
        private CurrentUserContextAccessorInterface $currentUser,
        private CurrentTenantContextAccessorInterface $currentTenant,
        private OrganizationUnitUserAccessCheckerInterface $access,
        private OrganizationUnitDirectoryInterface $organizationUnits,
        private TenantExecutionContextInterface $executionContext,
        private DatabaseManager $database,
        private ClockInterface $clock,
        private AuditRecorderInterface $audit,
    ) {}

    /** @return array{id:int,code:string,name:string,path:string} */
    public function execute(int $targetOrganizationUnitId): array
    {
        $user = $this->currentUser->requireCurrent();
        $tenantId = $this->currentTenant->requireCurrent()->tenantId();
        $sessionId = $this->positiveInt($user->tokenPayload()['session_id'] ?? null);
        if ($sessionId === null) {
            throw $this->invalidSession();
        }

        return $this->executionContext->runForTenant($tenantId, fn (): array => $this->database->transaction(
            function () use ($tenantId, $user, $targetOrganizationUnitId, $sessionId): array {
                $this->organizationUnits->assertActive($tenantId, [$targetOrganizationUnitId], true);
                if (! $this->access->canAccessOrganizationUnit(
                    $user->userId(),
                    $tenantId,
                    $targetOrganizationUnitId,
                    true,
                )) {
                    throw new AuthFailure(
                        AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                        'You do not have an active assignment to the selected organization unit.',
                        403,
                    );
                }

                $session = AuthSessionModel::query()
                    ->whereKey($sessionId)
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->userId())
                    ->lockForUpdate()
                    ->first();
                if (! $session instanceof AuthSessionModel
                    || (string) $session->getAttribute('status') !== SessionStatus::ACTIVE->value
                    || $this->clock->now()->getTimestamp() >= $session->getAttribute('expires_at')->getTimestamp()) {
                    throw $this->invalidSession();
                }

                $previous = $this->positiveInt($session->getAttribute('organization_unit_id'));
                if ($previous !== $targetOrganizationUnitId) {
                    $session->forceFill([
                        'organization_unit_id' => $targetOrganizationUnitId,
                        'last_activity_at' => $this->clock->now(),
                        'row_version' => (int) $session->getAttribute('row_version') + 1,
                    ])->save();

                    $this->audit->record(new AuditEventData(
                        eventName: 'auth.organization_unit.switched',
                        eventCategory: AuditEventCategory::AUTHORIZATION,
                        sourceModule: 'auth',
                        subjectType: 'auth_session',
                        subjectId: (string) $session->getAttribute('public_id'),
                        subjectReference: 'Organization-unit context switch',
                        changes: [
                            'before' => ['organization_unit_id' => $previous],
                            'after' => ['organization_unit_id' => $targetOrganizationUnitId],
                        ],
                        metadata: ['user_id' => $user->userId()],
                        tags: ['authentication', 'organization-unit', 'scope-switch'],
                    ));
                }

                $unit = $this->organizationUnits->summaries($tenantId, [$targetOrganizationUnitId])[$targetOrganizationUnitId] ?? null;
                if ($unit === null) {
                    throw new AuthFailure(
                        AuthErrorCode::ORGANIZATION_UNIT_RESOLUTION_FAILED,
                        'The selected organization unit is unavailable.',
                        409,
                    );
                }

                return $unit;
            },
            3,
        ));
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return null;
        }
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function invalidSession(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::SESSION_NOT_FOUND, 'The authentication session is unavailable.', 401);
    }
}
