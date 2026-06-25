<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantOnboardingStepStatus;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Models\TenantOnboardingStepModel;
use Modules\Tenant\Services\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class TenantFoundationCompletionPolicy
{
    public function __construct(
        private readonly TenantOnboardingStepModel $steps,
        private readonly TenantOrganizationProvisionerInterface $organizations,
        private readonly TenantAccessProvisionerInterface $access,
        private readonly TenantAuthenticationProvisionerInterface $authentication,
    ) {}

    /**
     * @return array{ready:bool,blockers:list<array{code:string,step:string,message:string}>}
     */
    public function inspect(TenantOnboardingStateModel $state, bool $lockForUpdate = false): array
    {
        $tenantId = (int) $state->getAttribute('tenant_id');
        $stepQuery = $this->steps->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereIn('step', TenantOnboardingStep::ordered());
        if ($lockForUpdate) {
            $stepQuery->lockForUpdate();
        }

        $statuses = $stepQuery->get(['step', 'status'])
            ->mapWithKeys(static fn (TenantOnboardingStepModel $step): array => [
                (string) $step->getAttribute('step') => (string) $step->getAttribute('status'),
            ])
            ->all();

        $rootId = $this->positiveInt($state->getAttribute('root_organization_unit_id'));
        $roleId = $this->positiveInt($state->getAttribute('super_admin_role_id'));
        $invitationId = $this->positiveInt($state->getAttribute('invitation_id'));
        $email = strtolower(trim((string) $state->getAttribute('initial_admin_email')));
        $invitation = $invitationId === null
            ? null
            : $this->authentication->initialAdministratorInvitationStatus(
                $tenantId,
                $invitationId,
                $lockForUpdate,
            );

        $checks = [
            TenantOnboardingStep::ROOT_ORGANIZATION => $rootId !== null
                && $this->organizations->isReady($tenantId, $rootId, $lockForUpdate),
            TenantOnboardingStep::PERMISSION_CATALOGUE => $this->access->catalogueIsReady($tenantId, $lockForUpdate),
            TenantOnboardingStep::SUPER_ADMIN_ROLE => $roleId !== null
                && $this->access->superAdminRoleIsReady($tenantId, $roleId, $lockForUpdate),
            TenantOnboardingStep::AUTHENTICATION_PROVIDER => $this->authentication->providerIsReady(
                $tenantId,
                $lockForUpdate,
            ),
            TenantOnboardingStep::INITIAL_ADMIN_INVITATION => $this->invitationIsUsable(
                $tenantId,
                $invitation,
                $invitationId,
                $rootId,
                $roleId,
                $email,
                $lockForUpdate,
            ),
        ];

        $messages = [
            TenantOnboardingStep::ROOT_ORGANIZATION => 'Provision the exact protected root organization for this tenant.',
            TenantOnboardingStep::PERMISSION_CATALOGUE => 'Synchronize the complete tenant permission catalogue.',
            TenantOnboardingStep::SUPER_ADMIN_ROLE => 'Provision the exact fully granted Super Admin role.',
            TenantOnboardingStep::AUTHENTICATION_PROVIDER => 'Provision an active tenant authentication provider.',
            TenantOnboardingStep::INITIAL_ADMIN_INVITATION => 'Issue a valid initial administrator invitation for the configured recipient.',
        ];

        $blockers = [];
        foreach (TenantOnboardingStep::ordered() as $step) {
            $stepCompleted = ($statuses[$step] ?? null) === TenantOnboardingStepStatus::COMPLETED;
            if ($stepCompleted && ($checks[$step] ?? false)) {
                continue;
            }

            $blockers[] = [
                'code' => 'TENANT_FOUNDATION_'.strtoupper($step).'_INCOMPLETE',
                'step' => $step,
                'message' => $messages[$step],
            ];
        }

        return ['ready' => $blockers === [], 'blockers' => $blockers];
    }

    /** @param array<string, mixed>|null $invitation */
    private function invitationIsUsable(
        int $tenantId,
        ?array $invitation,
        ?int $invitationId,
        ?int $organizationUnitId,
        ?int $roleId,
        string $email,
        bool $lockForUpdate,
    ): bool {
        if (
            $invitation === null
            || $invitationId === null
            || $organizationUnitId === null
            || $roleId === null
            || $email === ''
            || (int) ($invitation['id'] ?? 0) !== $invitationId
            || (int) ($invitation['organization_unit_id'] ?? 0) !== $organizationUnitId
            || (int) ($invitation['role_id'] ?? 0) !== $roleId
            || strtolower(trim((string) ($invitation['email'] ?? ''))) !== $email
        ) {
            return false;
        }

        $status = (string) ($invitation['status'] ?? '');
        if ($status === 'accepted') {
            return $this->positiveInt($invitation['accepted_by_user_id'] ?? null) !== null;
        }

        return $status === 'pending'
            && $this->authentication->hasPendingInitialAdministratorInvitation(
                $tenantId,
                $invitationId,
                $lockForUpdate,
            );
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
