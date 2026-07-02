<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantOnboardingStepStatus;
use Modules\Tenant\Models\TenantOnboardingStateModel;
use Modules\Tenant\Models\TenantOnboardingStepModel;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;

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
        $administratorUserId = $this->positiveInt($state->getAttribute('administrator_user_id'));
        $email = strtolower(trim((string) $state->getAttribute('initial_admin_email')));

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
            TenantOnboardingStep::INITIAL_ADMIN_ACCOUNT => $this->administratorAccountIsReady(
                $tenantId,
                $administratorUserId,
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
            TenantOnboardingStep::INITIAL_ADMIN_ACCOUNT => 'Create an active initial administrator account for the configured recipient.',
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

    private function administratorAccountIsReady(
        int $tenantId,
        ?int $administratorUserId,
        ?int $organizationUnitId,
        ?int $roleId,
        string $email,
        bool $lockForUpdate,
    ): bool {
        if (
            $administratorUserId === null
            || $organizationUnitId === null
            || $roleId === null
            || $email === ''
        ) {
            return false;
        }

        return $this->access->hasOperationalAdministrator(
            $tenantId,
            $administratorUserId,
            $organizationUnitId,
            $roleId,
            $lockForUpdate,
            $email,
        );
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
