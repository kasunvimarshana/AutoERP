<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use DateTimeInterface;
use InvalidArgumentException;
use Modules\Tenant\Constants\TenantStatus;

final class TenantLifecycleState
{
    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, match ($from) {
            TenantStatus::DRAFT => [TenantStatus::ACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::ACTIVE => [TenantStatus::SUSPENDED, TenantStatus::INACTIVE],
            TenantStatus::SUSPENDED => [TenantStatus::ACTIVE, TenantStatus::INACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::INACTIVE => [TenantStatus::ACTIVE, TenantStatus::ARCHIVED],
            TenantStatus::ARCHIVED => [],
            default => [],
        }, true);
    }

    /** @param array<string, mixed> $attributes */
    public static function assertValid(array $attributes): void
    {
        $status = strtolower(trim((string) ($attributes['status'] ?? '')));
        if (! TenantStatus::isValid($status)) {
            throw new InvalidArgumentException('Tenant status is invalid.');
        }
        if (! ($attributes['status_changed_at'] ?? null) instanceof DateTimeInterface
            && trim((string) ($attributes['status_changed_at'] ?? '')) === '') {
            throw new InvalidArgumentException('Tenant status_changed_at is required.');
        }

        $activated = $attributes['activated_at'] ?? null;
        $suspended = $attributes['suspended_at'] ?? null;
        $archived = $attributes['archived_at'] ?? null;

        if ($status === TenantStatus::DRAFT && ($activated !== null || $suspended !== null || $archived !== null)) {
            throw new InvalidArgumentException('A draft tenant cannot have lifecycle completion timestamps.');
        }
        if (in_array($status, [TenantStatus::ACTIVE, TenantStatus::SUSPENDED, TenantStatus::INACTIVE], true)
            && $activated === null) {
            throw new InvalidArgumentException('A non-draft operational tenant must have activated_at.');
        }
        if ($status === TenantStatus::SUSPENDED && $suspended === null) {
            throw new InvalidArgumentException('A suspended tenant must have suspended_at.');
        }
        if ($status === TenantStatus::ARCHIVED && $archived === null) {
            throw new InvalidArgumentException('An archived tenant must have archived_at.');
        }
        if ($status !== TenantStatus::ARCHIVED && $archived !== null) {
            throw new InvalidArgumentException('Only an archived tenant may have archived_at.');
        }
    }

    private function __construct() {}
}
