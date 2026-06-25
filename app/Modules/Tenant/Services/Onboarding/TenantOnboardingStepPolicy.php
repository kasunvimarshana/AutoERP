<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use InvalidArgumentException;
use Modules\Tenant\Constants\TenantOnboardingStep;
use Modules\Tenant\Constants\TenantOnboardingStepStatus;

final class TenantOnboardingStepPolicy
{
    /** @param array<string, mixed> $attributes */
    public static function assertValid(array $attributes): void
    {
        $step = (string) ($attributes['step'] ?? '');
        if (! in_array($step, TenantOnboardingStep::ordered(), true)) {
            throw new InvalidArgumentException('Tenant onboarding step is invalid.');
        }
        if ((string) ($attributes['owner_module'] ?? '') !== TenantOnboardingStep::owner($step)) {
            throw new InvalidArgumentException('Tenant onboarding step owner is invalid.');
        }

        $status = (string) ($attributes['status'] ?? '');
        if (! in_array($status, TenantOnboardingStepStatus::values(), true)) {
            throw new InvalidArgumentException('Tenant onboarding step status is invalid.');
        }

        $startedAt = $attributes['started_at'] ?? null;
        $completedAt = $attributes['completed_at'] ?? null;
        $operationId = trim((string) ($attributes['operation_id'] ?? ''));
        $errorCode = trim((string) ($attributes['error_code'] ?? ''));
        $errorMessage = trim((string) ($attributes['error_message'] ?? ''));

        if ($status === TenantOnboardingStepStatus::PENDING
            && ($startedAt !== null || $completedAt !== null || $operationId !== '' || $errorCode !== '' || $errorMessage !== '')) {
            throw new InvalidArgumentException('Pending onboarding step cannot retain execution state.');
        }
        if ($status === TenantOnboardingStepStatus::RUNNING
            && ($startedAt === null || $completedAt !== null || $operationId === '')) {
            throw new InvalidArgumentException('Running onboarding step requires operation ownership and started_at.');
        }
        if ($status === TenantOnboardingStepStatus::COMPLETED && $completedAt === null) {
            throw new InvalidArgumentException('Completed onboarding step requires completed_at.');
        }
        if ($status === TenantOnboardingStepStatus::FAILED && ($errorCode === '' || $errorMessage === '')) {
            throw new InvalidArgumentException('Failed onboarding step requires safe failure details.');
        }
    }

    private function __construct() {}
}
