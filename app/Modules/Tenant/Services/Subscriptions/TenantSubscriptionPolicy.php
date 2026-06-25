<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use DateTimeImmutable;
use Modules\Core\Contracts\ClockInterface;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantSubscriptionStatus;

final class TenantSubscriptionPolicy
{
    public const MISSING = 'missing';
    public const SCHEDULED = 'scheduled';

    public function __construct(private readonly ClockInterface $clock) {}

    /** @param array<string, mixed>|null $subscription */
    public function isUsable(?array $subscription): bool
    {
        $status = $this->statusAt($subscription);

        return $status === TenantSubscriptionStatus::TRIAL
            || $status === TenantSubscriptionStatus::ACTIVE;
    }

    /** @param array<string, mixed>|null $subscription */
    public function statusAt(?array $subscription, ?DateTimeImmutable $at = null): string
    {
        if ($subscription === null) {
            return self::MISSING;
        }

        $state = strtolower(trim((string) ($subscription['current_state'] ?? TenantCurrentSubscriptionState::ASSIGNED)));
        if ($state === TenantCurrentSubscriptionState::CANCELLED) {
            return TenantSubscriptionStatus::CANCELLED;
        }
        if ($state === TenantCurrentSubscriptionState::EXPIRED) {
            return TenantSubscriptionStatus::EXPIRED;
        }
        if ($state !== TenantCurrentSubscriptionState::ASSIGNED) {
            return self::MISSING;
        }

        $at ??= $this->clock->now();
        $startsAt = $this->dateTime($subscription['starts_at'] ?? null);
        if ($startsAt === null || $startsAt > $at) {
            return self::SCHEDULED;
        }

        $contractStatus = strtolower(trim((string) ($subscription['contract_status'] ?? '')));
        if ($contractStatus === TenantSubscriptionStatus::TRIAL) {
            $trialEndsAt = $this->dateTime($subscription['trial_ends_at'] ?? null);

            return $trialEndsAt !== null && $trialEndsAt > $at
                ? TenantSubscriptionStatus::TRIAL
                : TenantSubscriptionStatus::EXPIRED;
        }
        if ($contractStatus !== TenantSubscriptionStatus::ACTIVE) {
            return TenantSubscriptionStatus::EXPIRED;
        }

        $endsAt = $this->dateTime($subscription['ends_at'] ?? null);

        return $endsAt === null || $endsAt > $at
            ? TenantSubscriptionStatus::ACTIVE
            : TenantSubscriptionStatus::EXPIRED;
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : new DateTimeImmutable($value);
    }
}
