<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $logoPath = null,
        public bool $crossOrgTransactions = false,
        public ?int $tenantPlanId = null,
        public ?int $currencyId = null,
        public string $status = 'active',
        public ?string $trialEndsAt = null,
        public ?string $subscriptionEndsAt = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            logoPath: $data['logo_path'] ?? null,
            crossOrgTransactions: (bool) ($data['cross_org_transactions'] ?? false),
            tenantPlanId: isset($data['tenant_plan_id']) ? (int) $data['tenant_plan_id'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            status: (string) ($data['status'] ?? 'active'),
            trialEndsAt: $data['trial_ends_at'] ?? null,
            subscriptionEndsAt: $data['subscription_ends_at'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
