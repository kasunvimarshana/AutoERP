<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantValueData
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int|string $id,
        public string $uuid,
        public string $code,
        public string $name,
        public string $slug,
        public ?string $logoPath,
        public bool $crossOrgTransactions,
        public ?int $tenantPlanId,
        public ?int $currencyId,
        public string $status,
        public ?string $trialEndsAt,
        public ?string $subscriptionEndsAt,
        public bool $isActive,
        public bool $isIsolated,
        public ?string $isolationKey,
        public ?string $configurationScope,
        public array $metadata,
    ) {
    }
}
