<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Constants\TenantOnboardingErrorCode;
use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Exceptions\TenantOnboardingOperationException;
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
        private readonly TenantOrganizationProvisionerInterface $organizations,
        private readonly TenantAccessProvisionerInterface $access,
        private readonly TenantAuthenticationProvisionerInterface $authentication,
        private readonly TenantOnboardingProgressService $progress,
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

        $tenantId = (int) $tenant->id();
        $email = strtolower(trim($initialAdminEmail));
        $operationId = (string) Str::uuid();
        $correlationId = (string) Str::uuid();
        $currentStep = TenantOnboardingStep::ROOT_ORGANIZATION;

        try {
            $result = $this->executionContext->runForTenant(
                $tenantId,
                function () use (
                    $tenant,
                    $tenantId,
                    $expectedTenantVersion,
                    $email,
                    $operationId,
                    $correlationId,
                    &$currentStep,
                ): array {
                    $state = $this->progress->begin($tenantId, $email, $operationId, $correlationId);
                    $completed = $this->completedSteps($state->getAttribute('completed_steps'));

                    $organizationUnitId = $this->positiveInt($state->getAttribute('root_organization_unit_id'));
                    if (! in_array(TenantOnboardingStep::ROOT_ORGANIZATION, $completed, true) || $organizationUnitId === null) {
                        $currentStep = TenantOnboardingStep::ROOT_ORGANIZATION;
                        $this->progress->startStep($tenantId, $currentStep, $operationId, $correlationId);
                        $organization = $this->organizations->provision(
                            $tenantId,
                            (string) $tenant->require('code'),
                            (string) $tenant->require('name'),
                        );
                        $organizationUnitId = (int) $organization['organization_unit_id'];
                        $this->progress->completeStep($tenantId, $currentStep, $operationId, [
                            'root_organization_unit_id' => $organizationUnitId,
                        ]);
                    }

                    $roleId = $this->positiveInt($state->getAttribute('super_admin_role_id'));
                    if (
                        ! in_array(TenantOnboardingStep::PERMISSION_CATALOGUE, $completed, true)
                        || ! in_array(TenantOnboardingStep::SUPER_ADMIN_ROLE, $completed, true)
                        || $roleId === null
                        || ! $this->access->isReady($tenantId)
                    ) {
                        $currentStep = TenantOnboardingStep::PERMISSION_CATALOGUE;
                        $this->progress->startStep($tenantId, TenantOnboardingStep::PERMISSION_CATALOGUE, $operationId, $correlationId);
                        $this->progress->startStep($tenantId, TenantOnboardingStep::SUPER_ADMIN_ROLE, $operationId, $correlationId);
                        $access = $this->access->provision($tenantId);
                        $roleId = (int) $access['role_id'];
                        $this->progress->completeStep(
                            $tenantId,
                            TenantOnboardingStep::PERMISSION_CATALOGUE,
                            $operationId,
                        );
                        $this->progress->completeStep(
                            $tenantId,
                            TenantOnboardingStep::SUPER_ADMIN_ROLE,
                            $operationId,
                            ['super_admin_role_id' => $roleId],
                        );
                    }

                    if (! $this->authentication->providerIsReady($tenantId)) {
                        $currentStep = TenantOnboardingStep::AUTHENTICATION_PROVIDER;
                        $this->progress->startStep($tenantId, $currentStep, $operationId, $correlationId);
                        $this->authentication->provisionProvider($tenantId);
                        $this->progress->completeStep($tenantId, $currentStep, $operationId);
                    } elseif (! in_array(TenantOnboardingStep::AUTHENTICATION_PROVIDER, $completed, true)) {
                        $this->progress->completeStep(
                            $tenantId,
                            TenantOnboardingStep::AUTHENTICATION_PROVIDER,
                            $operationId,
                        );
                    }

                    $stateSnapshot = $this->progress->snapshot($tenantId) ?? [];
                    $invitationId = $this->positiveInt($stateSnapshot['invitation_id'] ?? null);
                    $invitation = $this->authentication->initialAdministratorInvitationStatus($tenantId, $invitationId);
                    $invitationUsable = is_array($invitation)
                        && in_array((string) ($invitation['status'] ?? ''), ['pending', 'accepted'], true)
                        && strtolower((string) ($invitation['email'] ?? '')) === $email;

                    if (! $invitationUsable) {
                        $currentStep = TenantOnboardingStep::INITIAL_ADMIN_INVITATION;
                        $this->progress->startStep($tenantId, $currentStep, $operationId, $correlationId);
                        $issued = $this->authentication->issueInitialAdministratorInvitation(
                            $tenantId,
                            $organizationUnitId,
                            $roleId,
                            $email,
                        );
                        $invitationId = (int) $issued['invitation_id'];
                        $this->progress->completeStep($tenantId, $currentStep, $operationId, [
                            'initial_admin_email' => $email,
                            'invitation_id' => $invitationId,
                        ]);
                        $invitation = $this->authentication->initialAdministratorInvitationStatus($tenantId, $invitationId);
                    } elseif (! in_array(TenantOnboardingStep::INITIAL_ADMIN_INVITATION, $completed, true)) {
                        $this->progress->completeStep($tenantId, TenantOnboardingStep::INITIAL_ADMIN_INVITATION, $operationId, [
                            'initial_admin_email' => $email,
                            'invitation_id' => $invitationId,
                        ]);
                    }

                    $currentStep = 'finalization';
                    $finalized = DB::transaction(function () use (
                        $tenantId,
                        $expectedTenantVersion,
                        $operationId,
                        $correlationId,
                        $email,
                        $tenant,
                    ): array {
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

                        $state = $this->progress->finishFoundation($tenantId, $operationId);
                        $updatedTenant = $this->tenants->updateWithVersion($tenantId, $expectedTenantVersion, [
                            'updated_by' => $this->currentUser->currentUserId(),
                        ]);
                        if ($updatedTenant === null) {
                            throw new RuntimeException('Tenant changed before onboarding could be finalized.');
                        }

                        $this->audit->recordPlatform(new AuditEventData(
                            eventName: 'tenant.onboarding.provisioned',
                            eventCategory: AuditEventCategory::ADMINISTRATION,
                            sourceModule: 'tenant',
                            subjectType: 'tenant',
                            subjectId: (string) $tenantId,
                            subjectReference: (string) $tenant->get('code'),
                            changes: ['new' => ['onboarding_status' => $state->getAttribute('status')]],
                            metadata: [
                                'initial_admin_email' => $email,
                                'correlation_id' => $correlationId,
                            ],
                            tags: ['tenant', 'onboarding'],
                        ), $tenantId);

                        return [
                            'status' => self::OUTCOME_SUCCESS,
                            'state' => $this->progress->serialize($state),
                            'tenant_row_version' => (int) $updatedTenant->require('row_version'),
                        ];
                    }, 3);

                    if ($finalized['status'] !== self::OUTCOME_SUCCESS) {
                        return $finalized;
                    }

                    return [
                        ...$finalized,
                        'permission_count' => $this->access->permissionCount($tenantId),
                        'invitation' => $invitation,
                        'correlation_id' => $correlationId,
                    ];
                },
            );

            if (($result['status'] ?? null) === self::OUTCOME_NOT_FOUND) {
                return $this->notFound();
            }
            if (($result['status'] ?? null) === self::OUTCOME_VERSION_CONFLICT) {
                $this->recordSafeFailure(
                    $tenantId,
                    'finalization',
                    $operationId,
                    $correlationId,
                    TenantOnboardingErrorCode::FINALIZATION_FAILED,
                    'Tenant details changed during foundation provisioning. Refresh and retry finalization.',
                    null,
                );

                return $this->versionConflict();
            }
            if (($result['status'] ?? null) === self::OUTCOME_INVALID_STATUS) {
                return $this->invalidStatus();
            }

            try {
                $result['readiness'] = $this->readiness->inspect($tenantId);
            } catch (Throwable $readinessFailure) {
                $this->logger->warning('Tenant onboarding readiness could not be refreshed after successful provisioning.', [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                    'exception' => $readinessFailure,
                ]);
                $result['readiness'] = null;
            }

            unset($result['status']);

            return Result::success($result);
        } catch (TenantOnboardingOperationException $exception) {
            $this->logger->notice('Tenant onboarding operation was rejected.', [
                'tenant_id' => $tenantId,
                'correlation_id' => $exception->correlationId ?? $correlationId,
                'error_code' => $exception->errorCode,
                'step' => $exception->step,
            ]);

            return Result::failure(new Error(
                TenantErrorCode::INVALID_VALUE,
                $exception->getMessage(),
                [
                    'error_code' => $exception->errorCode,
                    'failed_step' => $exception->step,
                    'correlation_id' => $exception->correlationId ?? $correlationId,
                ],
            ));
        } catch (Throwable $exception) {
            $safeStep = $currentStep;
            $errorCode = TenantOnboardingErrorCode::forStep($safeStep);
            $safeMessage = TenantOnboardingErrorCode::safeMessage($safeStep);
            $this->recordSafeFailure(
                $tenantId,
                $safeStep,
                $operationId,
                $correlationId,
                $errorCode,
                $safeMessage,
                $exception,
            );

            return Result::failure(new Error(
                TenantErrorCode::INVALID_VALUE,
                $safeMessage,
                [
                    'error_code' => $errorCode,
                    'failed_step' => $safeStep,
                    'correlation_id' => $correlationId,
                ],
            ));
        }
    }

    private function recordSafeFailure(
        int $tenantId,
        string $step,
        string $operationId,
        string $correlationId,
        string $errorCode,
        string $safeMessage,
        ?Throwable $exception,
    ): void {
        $this->logger->error('Tenant onboarding failed.', [
            'tenant_id' => $tenantId,
            'step' => $step,
            'error_code' => $errorCode,
            'correlation_id' => $correlationId,
            'exception' => $exception,
        ]);

        try {
            $this->executionContext->runForTenant(
                $tenantId,
                fn (): mixed => $this->progress->failStep(
                    $tenantId,
                    $step,
                    $operationId,
                    $correlationId,
                    $errorCode,
                    $safeMessage,
                ),
            );
        } catch (Throwable $secondaryFailure) {
            $this->logger->error('Tenant onboarding failure state could not be persisted.', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'exception' => $secondaryFailure,
            ]);
        }
    }

    /** @return list<string> */
    private function completedSteps(mixed $value): array
    {
        return is_array($value)
            ? array_values(array_filter($value, static fn (mixed $step): bool => is_string($step)))
            : [];
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
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
