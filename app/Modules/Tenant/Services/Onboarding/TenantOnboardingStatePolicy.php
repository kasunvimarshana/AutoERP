<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Onboarding;

use InvalidArgumentException;
use Modules\Tenant\Constants\TenantOnboardingStatus;

final class TenantOnboardingStatePolicy
{
    /** @param array<string, mixed> $attributes */
    public static function assertValid(array $attributes): void
    {
        $status = (string) ($attributes['status'] ?? '');
        if (! in_array($status, TenantOnboardingStatus::values(), true)) {
            throw new InvalidArgumentException('Tenant onboarding status is invalid.');
        }

        $operationId = self::present($attributes['operation_id'] ?? null);
        $startedAt = $attributes['operation_started_at'] ?? null;
        $lease = $attributes['operation_lease_expires_at'] ?? null;
        $failedStep = self::present($attributes['failed_step'] ?? null);
        $errorCode = self::present($attributes['last_error_code'] ?? null);
        $errorMessage = self::present($attributes['last_error_message'] ?? null);
        $completedAt = $attributes['completed_at'] ?? null;

        if ($status === TenantOnboardingStatus::PROVISIONING) {
            if (! $operationId || $startedAt === null || $lease === null) {
                throw new InvalidArgumentException('Provisioning state requires an owned operation and lease.');
            }
        } elseif ($operationId || $startedAt !== null || $lease !== null) {
            throw new InvalidArgumentException('Only provisioning state may hold an operation lease.');
        }

        if ($status === TenantOnboardingStatus::FAILED) {
            if (! $failedStep || ! $errorCode || ! $errorMessage) {
                throw new InvalidArgumentException('Failed onboarding state requires safe failure details.');
            }
        } elseif ($failedStep || $errorCode || $errorMessage) {
            throw new InvalidArgumentException('Non-failed onboarding state cannot retain failure details.');
        }

        if ($status === TenantOnboardingStatus::COMPLETED && $completedAt === null) {
            throw new InvalidArgumentException('Completed onboarding state requires completed_at.');
        }
        if ($status !== TenantOnboardingStatus::COMPLETED && $completedAt !== null) {
            throw new InvalidArgumentException('Only completed onboarding state may have completed_at.');
        }
    }

    private static function present(mixed $value): bool
    {
        return trim((string) ($value ?? '')) !== '';
    }

    private function __construct() {}
}
