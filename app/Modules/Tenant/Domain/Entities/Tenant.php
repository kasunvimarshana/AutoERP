<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use DateTimeImmutable;
use Modules\Tenant\Domain\ValueObjects\TenantPlan;
use Modules\Tenant\Domain\ValueObjects\TenantStatus;

final class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $slug,
        public readonly TenantStatus $status,
        public readonly TenantPlan $plan,
        public readonly ?string $domain,
        public readonly ?string $logoPath,
        public readonly ?array $settings,
        public readonly ?DateTimeImmutable $trialEndsAt,
        public readonly ?DateTimeImmutable $subscriptionEndsAt,
        public readonly ?array $metadata,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly ?DateTimeImmutable $deletedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            uuid: $data['uuid'],
            name: $data['name'],
            slug: $data['slug'],
            status: new TenantStatus($data['status']),
            plan: new TenantPlan($data['plan']),
            domain: $data['domain'] ?? null,
            logoPath: $data['logo_path'] ?? null,
            settings: isset($data['settings']) ? (array) $data['settings'] : null,
            trialEndsAt: isset($data['trial_ends_at']) ? new DateTimeImmutable($data['trial_ends_at']) : null,
            subscriptionEndsAt: isset($data['subscription_ends_at']) ? new DateTimeImmutable($data['subscription_ends_at']) : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            deletedAt: isset($data['deleted_at']) ? new DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTrial(): bool
    {
        return $this->status->isTrial();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }
}
