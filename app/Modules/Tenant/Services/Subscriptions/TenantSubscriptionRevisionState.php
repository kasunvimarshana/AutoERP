<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Modules\Tenant\Constants\TenantSubscriptionOperation;
use Modules\Tenant\Constants\TenantSubscriptionStatus;

final class TenantSubscriptionRevisionState
{
    /** @param array<string, mixed> $attributes */
    public static function assertValid(array $attributes): void
    {
        $operation = (string) ($attributes['operation'] ?? '');
        if (! in_array($operation, TenantSubscriptionOperation::values(), true)) {
            throw new InvalidArgumentException('Tenant subscription operation is invalid.');
        }

        $status = (string) ($attributes['contract_status'] ?? '');
        if (! in_array($status, TenantSubscriptionStatus::assignable(), true)) {
            throw new InvalidArgumentException('Tenant subscription contract status is invalid.');
        }

        $startsAt = self::date($attributes['starts_at'] ?? null, 'starts_at');
        $trialEndsAt = self::nullableDate($attributes['trial_ends_at'] ?? null, 'trial_ends_at');
        $endsAt = self::nullableDate($attributes['ends_at'] ?? null, 'ends_at');

        if ($status === TenantSubscriptionStatus::TRIAL) {
            if ($trialEndsAt === null || $trialEndsAt <= $startsAt) {
                throw new InvalidArgumentException('A trial subscription requires trial_ends_at after starts_at.');
            }
            if ($endsAt !== null) {
                throw new InvalidArgumentException('A trial subscription cannot also define ends_at.');
            }
        }

        if ($status === TenantSubscriptionStatus::ACTIVE) {
            if ($trialEndsAt !== null) {
                throw new InvalidArgumentException('An active subscription cannot define trial_ends_at.');
            }
            if ($endsAt !== null && $endsAt <= $startsAt) {
                throw new InvalidArgumentException('Subscription ends_at must be after starts_at.');
            }
        }

        $supersedes = $attributes['supersedes_subscription_id'] ?? null;
        if ($operation === TenantSubscriptionOperation::ASSIGN && $supersedes !== null) {
            throw new InvalidArgumentException('An initial assignment cannot supersede another subscription.');
        }
        if ($operation !== TenantSubscriptionOperation::ASSIGN && (! is_numeric($supersedes) || (int) $supersedes < 1)) {
            throw new InvalidArgumentException('Renew, extend, and correct operations must supersede the current revision.');
        }

        foreach (['plan_features_schema_version', 'plan_limits_schema_version'] as $field) {
            if (! is_numeric($attributes[$field] ?? null) || (int) $attributes[$field] < 1) {
                throw new InvalidArgumentException("{$field} must be a positive integer.");
            }
        }
    }

    private static function nullableDate(mixed $value, string $field): ?DateTimeImmutable
    {
        return $value === null || $value === '' ? null : self::date($value, $field);
    }

    private static function date(mixed $value, string $field): DateTimeImmutable
    {
        try {
            if ($value instanceof CarbonInterface) {
                return DateTimeImmutable::createFromInterface($value);
            }
            if ($value instanceof DateTimeInterface) {
                return DateTimeImmutable::createFromInterface($value);
            }
            if (is_string($value) && trim($value) !== '') {
                return new DateTimeImmutable($value);
            }
        } catch (\Throwable) {
        }

        throw new InvalidArgumentException("Tenant subscription {$field} is invalid.");
    }

    private function __construct() {}
}
