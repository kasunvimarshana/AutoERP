<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Contracts\ClockInterface;
use Modules\Core\DTOs\DataRecord;

final class TenantSubscriptionWindowPolicy
{
    public function __construct(private readonly ClockInterface $clock) {}

    public function isValid(DataRecord $tenant): bool
    {
        return $this->blockingMessage($tenant) === null;
    }

    public function blockingMessage(DataRecord $tenant): ?string
    {
        $now = $this->clock->now()->getTimestamp();
        $subscriptionEndsAt = $this->timestamp($tenant->get('subscription_ends_at'));
        if ($subscriptionEndsAt !== null) {
            return $subscriptionEndsAt < $now
                ? 'The selected tenant subscription has expired.'
                : null;
        }

        $trialEndsAt = $this->timestamp($tenant->get('trial_ends_at'));
        if ($trialEndsAt !== null && $trialEndsAt < $now) {
            return 'The selected tenant trial has expired.';
        }

        return null;
    }

    private function timestamp(mixed $value): ?int
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $timestamp = is_scalar($value) ? strtotime((string) $value) : false;

        return $timestamp === false ? 0 : $timestamp;
    }
}
