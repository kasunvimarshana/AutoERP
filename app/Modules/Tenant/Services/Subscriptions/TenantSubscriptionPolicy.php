<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use DateTimeImmutable;
use Modules\Core\Contracts\ClockInterface;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantSubscriptionStatus;
use Modules\Tenant\Exceptions\TenantSubscriptionDataException;
use Throwable;

final class TenantSubscriptionPolicy
{
    public const MISSING = 'missing';
    public const SCHEDULED = 'scheduled';
    public const INVALID = 'invalid';

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
        $startsAt = $this->dateTime('starts_at', $subscription['starts_at'] ?? null);
        if ($startsAt === null || $startsAt > $at) {
            return self::SCHEDULED;
        }

        $contractStatus = strtolower(trim((string) ($subscription['contract_status'] ?? '')));
        if ($contractStatus === TenantSubscriptionStatus::TRIAL) {
            $trialEndsAt = $this->dateTime('trial_ends_at', $subscription['trial_ends_at'] ?? null);

            return $trialEndsAt !== null && $trialEndsAt > $at
                ? TenantSubscriptionStatus::TRIAL
                : TenantSubscriptionStatus::EXPIRED;
        }
        if ($contractStatus !== TenantSubscriptionStatus::ACTIVE) {
            return TenantSubscriptionStatus::EXPIRED;
        }

        $endsAt = $this->dateTime('ends_at', $subscription['ends_at'] ?? null);

        return $endsAt === null || $endsAt > $at
            ? TenantSubscriptionStatus::ACTIVE
            : TenantSubscriptionStatus::EXPIRED;
    }

    private function dateTime(string $field, mixed $value): ?DateTimeImmutable
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            throw TenantSubscriptionDataException::invalidDateTime($field, $value);
        }
    }
}
