<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantMutationData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $slug,
        public ?string $logoPath,
        public bool $crossOrgTransactions,
        public ?int $tenantPlanId,
        public ?int $currencyId,
        public ?string $status,
        public ?string $trialEndsAt,
        public ?string $subscriptionEndsAt,
        public bool $isIsolated,
        public ?string $isolationKey,
        public ?string $configurationScope,
        public ?string $logoTmpPath,
        public ?string $logoOriginalName,
        public array $metadata = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['code'] ?? ''),
            (string) ($payload['name'] ?? ''),
            isset($payload['slug']) ? (string) $payload['slug'] : null,
            isset($payload['logo_path']) ? (string) $payload['logo_path'] : null,
            isset($payload['cross_org_transactions']) ? (bool) $payload['cross_org_transactions'] : false,
            isset($payload['tenant_plan_id']) ? (int) $payload['tenant_plan_id'] : null,
            isset($payload['currency_id']) ? (int) $payload['currency_id'] : null,
            isset($payload['status']) ? (string) $payload['status'] : null,
            isset($payload['trial_ends_at']) ? (string) $payload['trial_ends_at'] : null,
            isset($payload['subscription_ends_at']) ? (string) $payload['subscription_ends_at'] : null,
            isset($payload['is_isolated']) ? (bool) $payload['is_isolated'] : true,
            isset($payload['isolation_key']) ? (string) $payload['isolation_key'] : null,
            isset($payload['configuration_scope']) ? (string) $payload['configuration_scope'] : null,
            isset($payload['logo_tmp_path']) ? (string) $payload['logo_tmp_path'] : null,
            isset($payload['logo_original_name']) ? (string) $payload['logo_original_name'] : null,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }
}
