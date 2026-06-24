<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantOnboardingService
{
    private const OUTCOME_SUCCESS = 'success';
    private const OUTCOME_NOT_FOUND = 'not_found';
    private const OUTCOME_VERSION_CONFLICT = 'version_conflict';
    private const OUTCOME_INVALID_STATUS = 'invalid_status';

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantOnboardingStateModel $states,
        private readonly TenantOrganizationProvisionerInterface $organizations,
        private readonly TenantAccessProvisionerInterface $access,
        private readonly TenantAuthenticationProvisionerInterface $authentication,
        private readonly TenantReadinessService $readiness,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly LoggerInterface $logger,
    ) {}

    public function provision(
        int|string $tenantId,
        int $expectedTenantVersion,
        string $initialAdminEmail,
    ): Result {
        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return $this->notFound();
        }
        if ($expectedTenantVersion < 1 || (int) $tenant->require('row_version') !== $expectedTenantVersion) {
            return $this->versionConflict();
        }
        if ((string) $tenant->require('status') !== TenantStatus::DRAFT) {
            return $this->invalidStatus();
        }

        $email = strtolower(trim($initialAdminEmail));
        $tenantId = (int) $tenant->id();

        try {
            /** @var array{status:string,result?:array<string,mixed>} $outcome */
            $outcome = $this->executionContext->runForTenant(
                $tenantId,
                fn (): array => DB::transaction(function () use ($tenantId, $expectedTenantVersion, $email): array {
                    $lockedTenant = $this->tenants->lockById($tenantId);
                    if ($lockedTenant === null) {
                        return ['status' => self::OUTCOME_NOT_FOUND];
                    }
                    if ((int) $lockedTenant->require('row_version') !== $expectedTenantVersion) {
                        return ['status' => self::OUTCOME_VERSION_CONFLICT];
                    }
                    if ((string) $lockedTenant->require('status') !== TenantStatus::DRAFT) {
                        return ['status' => self::OUTCOME_INVALID_STATUS];
                    }

                    $state = $this->states->newQuery()
                        ->where('tenant_id', $tenantId)
                        ->lockForUpdate()
                        ->first();

                    if (! $state instanceof TenantOnboardingStateModel) {
                        $state = $this->states->newQuery()->create([
                            'tenant_id' => $tenantId,
                            'status' => TenantOnboardingStatus::PENDING,
                            'row_version' => 1,
                            'created_by' => $this->currentUser->currentUserId(),
                            'updated_by' => $this->currentUser->currentUserId(),
                        ]);
                    }

                    $existingEmail = strtolower(trim((string) $state->getAttribute('initial_admin_email')));
                    if ($existingEmail !== '' && $existingEmail !== $email) {
                        throw new \DomainException(
                            'The initial administrator email is already fixed for this onboarding. Revoke the existing invitation before changing it.',
                        );
                    }

                    $state->forceFill([
                        'status' => TenantOnboardingStatus::PROVISIONING,
                        'initial_admin_email' => $email,
                        'last_error' => null,
                        'row_version' => (int) $state->getAttribute('row_version') + 1,
                        'updated_by' => $this->currentUser->currentUserId(),
                    ])->save();

                    $organization = $this->organizations->provision(
                        $tenantId,
                        (string) $lockedTenant->require('code'),
                        (string) $lockedTenant->require('name'),
                    );
                    $access = $this->access->provision($tenantId);

                    $invitationToken = null;
                    $invitationId = $state->getAttribute('invitation_id');
                    if (! is_numeric($invitationId) || ! $this->authentication->isReady($tenantId)) {
                        $authentication = $this->authentication->provisionInitialAdministrator(
                            $tenantId,
                            $organization['organization_unit_id'],
                            $access['role_id'],
                            $email,
                        );
                        $invitationId = $authentication['invitation_id'];
                        $invitationToken = $authentication['invitation_token'];
                        $invitationExpiresAt = $authentication['invitation_expires_at'];
                    } else {
                        $invitationExpiresAt = null;
                    }

                    $state->forceFill([
                        'status' => TenantOnboardingStatus::AWAITING_DOMAIN,
                        'root_organization_unit_id' => $organization['organization_unit_id'],
                        'super_admin_role_id' => $access['role_id'],
                        'invitation_id' => (int) $invitationId,
                        'completed_steps' => [
                            'organization_structure',
                            'permission_catalogue',
                            'super_admin_role',
                            'authentication_provider',
                            'initial_admin_invitation',
                        ],
                        'provisioned_at' => now(),
                        'row_version' => (int) $state->getAttribute('row_version') + 1,
                        'updated_by' => $this->currentUser->currentUserId(),
                    ])->save();

                    $updatedTenant = $this->tenants->updateWithVersion($tenantId, $expectedTenantVersion, [
                        'updated_by' => $this->currentUser->currentUserId(),
                    ]);
                    if ($updatedTenant === null) {
                        throw new RuntimeException('Locked tenant could not be versioned during onboarding.');
                    }

                    return [
                        'status' => self::OUTCOME_SUCCESS,
                        'result' => [
                            'state' => $state->fresh()?->attributesToArray() ?? $state->attributesToArray(),
                            'invitation_token' => $invitationToken,
                            'invitation_expires_at' => $invitationExpiresAt,
                            'permission_count' => $access['permission_count'],
                            'tenant_row_version' => (int) $updatedTenant->require('row_version'),
                        ],
                    ];
                }, 3),
            );

            if ($outcome['status'] === self::OUTCOME_NOT_FOUND) {
                return $this->notFound();
            }
            if ($outcome['status'] === self::OUTCOME_VERSION_CONFLICT) {
                return $this->versionConflict();
            }
            if ($outcome['status'] === self::OUTCOME_INVALID_STATUS) {
                return $this->invalidStatus();
            }

            $result = $outcome['result'] ?? null;
            if (! is_array($result)) {
                throw new RuntimeException('Tenant onboarding completed without a result payload.');
            }

            $this->audit->recordPlatform(new AuditEventData(
                eventName: 'tenant.onboarding.provisioned',
                eventCategory: AuditEventCategory::ADMINISTRATION,
                sourceModule: 'tenant',
                subjectType: 'tenant',
                subjectId: (string) $tenantId,
                subjectReference: (string) $tenant->get('code'),
                changes: ['new' => ['onboarding_status' => TenantOnboardingStatus::AWAITING_DOMAIN]],
                metadata: ['initial_admin_email' => $email],
                tags: ['tenant', 'onboarding'],
            ), $tenantId);

            return Result::success([
                ...$result,
                'readiness' => $this->readiness->inspect($tenantId),
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($tenantId, $email, $exception->getMessage());

            return Result::failure(new Error(
                TenantErrorCode::INVALID_VALUE,
                $exception->getMessage(),
            ));
        }
    }

    private function markFailed(int $tenantId, string $email, string $message): void
    {
        try {
            $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $email, $message): void {
                $state = $this->states->newQuery()->firstOrNew(['tenant_id' => $tenantId]);
                $state->forceFill([
                    'status' => TenantOnboardingStatus::FAILED,
                    'initial_admin_email' => $email,
                    'last_error' => mb_substr($message, 0, 1000),
                    'row_version' => max(1, (int) $state->getAttribute('row_version')) + 1,
                    'updated_by' => $this->currentUser->currentUserId(),
                ])->save();
            });
        } catch (Throwable $secondaryFailure) {
            $this->logger->error('Tenant onboarding failure state could not be persisted.', [
                'tenant_id' => $tenantId,
                'initial_admin_email' => $email,
                'original_error' => $message,
                'secondary_error' => $secondaryFailure->getMessage(),
                'exception' => $secondaryFailure,
            ]);
        }
    }

    private function notFound(): Result
    {
        return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Tenant changed since it was loaded. Refresh and try again.',
        ));
    }

    private function invalidStatus(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::INVALID_TRANSITION,
            'Only a draft tenant can be provisioned.',
        ));
    }
}
