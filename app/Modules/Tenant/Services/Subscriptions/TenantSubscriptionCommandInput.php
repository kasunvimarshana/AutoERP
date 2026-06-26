<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantSubscriptionOperation;
use Modules\Tenant\Constants\TenantSubscriptionStatus;

final class TenantSubscriptionCommandInput
{
    public function __construct(private readonly ClockInterface $clock) {}

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     plan_revision_id:int,
     *     contract_status:string,
     *     period:array{starts_at:string,trial_ends_at:?string,ends_at:?string}
     * }
     */
    public function resolve(string $operation, array $payload, ?DataRecord $current): array
    {
        if ($operation === TenantSubscriptionOperation::EXTEND) {
            return $this->resolveActiveExtension($payload, $current);
        }

        $planRevisionId = $this->positiveInt($payload['tenant_plan_revision_id'] ?? null);
        if ($planRevisionId === null) {
            throw new InvalidArgumentException('Select a tenant plan revision.');
        }

        $contractStatus = strtolower(trim((string) ($payload['contract_status'] ?? '')));
        if (! in_array($contractStatus, TenantSubscriptionStatus::assignable(), true)) {
            throw new InvalidArgumentException('Subscription contract status must be trial or active.');
        }

        if ($operation === TenantSubscriptionOperation::RENEW && $current === null) {
            throw new InvalidArgumentException('A current subscription is required before it can be renewed.');
        }

        return [
            'plan_revision_id' => $planRevisionId,
            'contract_status' => $contractStatus,
            'period' => $this->period($payload, $contractStatus),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *     plan_revision_id:int,
     *     contract_status:string,
     *     period:array{starts_at:string,trial_ends_at:?string,ends_at:?string}
     * }
     */
    private function resolveActiveExtension(array $payload, ?DataRecord $current): array
    {
        if ($current === null) {
            throw new InvalidArgumentException('A current subscription is required before it can be extended.');
        }
        if ((string) $current->get('contract_status') !== TenantSubscriptionStatus::ACTIVE) {
            throw new InvalidArgumentException(
                'Only active fixed-term subscriptions can be extended. Use Correct subscription to change a trial end date.',
            );
        }

        $currentEnd = $this->dateTime($current->get('ends_at'));
        if ($currentEnd === null) {
            throw new InvalidArgumentException('An open-ended subscription cannot be extended.');
        }

        $newEnd = $this->dateTime($payload['ends_at'] ?? null);
        if ($newEnd === null || $newEnd <= $currentEnd) {
            throw new InvalidArgumentException('The extended end date must be later than the current end date.');
        }

        $startsAt = $this->dateTime($current->require('starts_at'))
            ?? throw new InvalidArgumentException('Current subscription start date is invalid.');

        return [
            'plan_revision_id' => (int) $current->require('tenant_plan_revision_id'),
            'contract_status' => TenantSubscriptionStatus::ACTIVE,
            'period' => [
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'trial_ends_at' => null,
                'ends_at' => $newEnd->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{starts_at:string,trial_ends_at:?string,ends_at:?string}
     */
    private function period(array $payload, string $contractStatus): array
    {
        $now = $this->clock->now();
        $startsAt = $this->dateTime($payload['starts_at'] ?? null) ?? $now;
        if ($startsAt > $now) {
            throw new InvalidArgumentException(
                'Future subscription starts are not supported until scheduled activation is enabled. Choose the current time or an earlier time.',
            );
        }

        $trialEndsAt = $this->dateTime($payload['trial_ends_at'] ?? null);
        $endsAt = $this->dateTime($payload['ends_at'] ?? null);

        if ($contractStatus === TenantSubscriptionStatus::TRIAL) {
            if ($trialEndsAt === null) {
                throw new InvalidArgumentException('A trial end date is required for a trial subscription.');
            }
            if ($trialEndsAt <= $startsAt) {
                throw new InvalidArgumentException('Trial end date must be later than the subscription start date.');
            }
            if ($endsAt !== null) {
                throw new InvalidArgumentException('A trial subscription cannot also define a contract end date.');
            }

            return [
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'trial_ends_at' => $trialEndsAt->format('Y-m-d H:i:s'),
                'ends_at' => null,
            ];
        }

        if ($endsAt !== null && $endsAt <= $startsAt) {
            throw new InvalidArgumentException('Subscription end date must be later than the subscription start date.');
        }

        return [
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'trial_ends_at' => null,
            'ends_at' => $endsAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : new DateTimeImmutable($value);
    }
}
