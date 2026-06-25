<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantAdministratorInvitationService
{
    public function __construct(
        private readonly TenantAuthenticationProvisionerInterface $authentication,
        private readonly TenantOnboardingProgressService $progress,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly AuditRecorderInterface $audit,
    ) {}

    /** @return array{onboarding:array<string,mixed>,invitation:array<string,mixed>|null} */
    public function inspect(int $tenantId): array
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId): array {
            $state = $this->requireState($tenantId);
            $invitationId = $this->positiveInt($state['invitation_id'] ?? null);

            return [
                'onboarding' => $state,
                'invitation' => $this->authentication->initialAdministratorInvitationStatus(
                    $tenantId,
                    $invitationId,
                ),
            ];
        });
    }

    /** @return array{onboarding:array<string,mixed>,invitation:array<string,mixed>|null} */
    public function resend(int $tenantId, int $invitationId, int $expectedInvitationVersion): array
    {
        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId,
            $invitationId,
            $expectedInvitationVersion,
        ): array {
            $this->assertInvitationBelongsToOnboarding($tenantId, $invitationId);

            try {
                $invitation = $this->authentication->resendInitialAdministratorInvitation(
                    $tenantId,
                    $invitationId,
                    $expectedInvitationVersion,
                );
            } catch (RuntimeException $exception) {
                throw new ConflictHttpException('The invitation changed, expired, or cannot be resent.', $exception);
            }

            $this->record($tenantId, 'tenant.onboarding.initial_administrator_invitation_resent', [
                'invitation_id' => $invitationId,
                'email' => $invitation['email'] ?? null,
            ]);

            return [
                'onboarding' => $this->requireState($tenantId),
                'invitation' => $invitation,
            ];
        });
    }

    /** @return array{onboarding:array<string,mixed>,invitation:null} */
    public function revoke(
        int $tenantId,
        int $invitationId,
        int $expectedInvitationVersion,
        int $expectedOnboardingVersion,
        string $reason,
    ): array {
        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId,
            $invitationId,
            $expectedInvitationVersion,
            $expectedOnboardingVersion,
            $reason,
        ): array {
            try {
                return DB::transaction(function () use (
                    $tenantId,
                    $invitationId,
                    $expectedInvitationVersion,
                    $expectedOnboardingVersion,
                    $reason,
                ): array {
                    $this->assertInvitationBelongsToOnboarding($tenantId, $invitationId);
                    $this->authentication->revokeInitialAdministratorInvitation(
                        $tenantId,
                        $invitationId,
                        $expectedInvitationVersion,
                        $reason,
                    );
                    $state = $this->progress->clearInvitationReference(
                        $tenantId,
                        $expectedOnboardingVersion,
                    );
                    $this->record($tenantId, 'tenant.onboarding.initial_administrator_invitation_revoked', [
                        'invitation_id' => $invitationId,
                        'reason' => trim($reason),
                    ]);

                    return [
                        'onboarding' => $this->progress->serialize($state),
                        'invitation' => null,
                    ];
                }, 3);
            } catch (RuntimeException $exception) {
                throw new ConflictHttpException('The invitation or onboarding state changed. Refresh and try again.', $exception);
            }
        });
    }

    /** @return array{onboarding:array<string,mixed>,invitation:array<string,mixed>|null} */
    public function replace(
        int $tenantId,
        int $invitationId,
        int $expectedInvitationVersion,
        int $expectedOnboardingVersion,
        string $email,
        string $reason,
    ): array {
        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId,
            $invitationId,
            $expectedInvitationVersion,
            $expectedOnboardingVersion,
            $email,
            $reason,
        ): array {
            try {
                return DB::transaction(function () use (
                    $tenantId,
                    $invitationId,
                    $expectedInvitationVersion,
                    $expectedOnboardingVersion,
                    $email,
                    $reason,
                ): array {
                    $state = $this->requireState($tenantId);
                    $this->assertInvitationBelongsToOnboarding($tenantId, $invitationId, $state);
                    $organizationUnitId = $this->positiveInt($state['root_organization_unit_id'] ?? null);
                    $roleId = $this->positiveInt($state['super_admin_role_id'] ?? null);
                    if ($organizationUnitId === null || $roleId === null) {
                        throw new ConflictHttpException('Tenant foundation access must be provisioned before replacing the invitation.');
                    }

                    $issued = $this->authentication->replaceInitialAdministratorInvitation(
                        $tenantId,
                        $invitationId,
                        $expectedInvitationVersion,
                        $organizationUnitId,
                        $roleId,
                        $email,
                        $reason,
                    );
                    $updated = $this->progress->replaceInvitationReference(
                        $tenantId,
                        $expectedOnboardingVersion,
                        $email,
                        $issued,
                    );
                    $this->record($tenantId, 'tenant.onboarding.initial_administrator_invitation_replaced', [
                        'previous_invitation_id' => $invitationId,
                        'invitation_id' => $issued['invitation_id'],
                        'email' => strtolower(trim($email)),
                        'reason' => trim($reason),
                    ]);

                    return [
                        'onboarding' => $this->progress->serialize($updated),
                        'invitation' => $this->authentication->initialAdministratorInvitationStatus(
                            $tenantId,
                            (int) $issued['invitation_id'],
                        ),
                    ];
                }, 3);
            } catch (RuntimeException $exception) {
                throw new ConflictHttpException('The invitation or onboarding state changed. Refresh and try again.', $exception);
            }
        });
    }

    /** @return array<string, mixed> */
    private function requireState(int $tenantId): array
    {
        return $this->progress->snapshot($tenantId)
            ?? throw new NotFoundHttpException('Tenant onboarding state was not found.');
    }

    /** @param array<string,mixed>|null $state */
    private function assertInvitationBelongsToOnboarding(
        int $tenantId,
        int $invitationId,
        ?array $state = null,
    ): void {
        $state ??= $this->requireState($tenantId);
        if ($this->positiveInt($state['invitation_id'] ?? null) !== $invitationId) {
            throw new NotFoundHttpException('The initial administrator invitation was not found for this onboarding workflow.');
        }
    }

    /** @param array<string,mixed> $metadata */
    private function record(int $tenantId, string $eventName, array $metadata): void
    {
        $this->audit->recordPlatform(new AuditEventData(
            eventName: $eventName,
            eventCategory: AuditEventCategory::ADMINISTRATION,
            sourceModule: 'tenant',
            subjectType: 'tenant',
            subjectId: (string) $tenantId,
            metadata: $metadata,
            tags: ['tenant', 'onboarding', 'invitation'],
        ), $tenantId);
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
