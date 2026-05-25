<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use InvalidArgumentException;
use Modules\Core\Domain\Entities\Entity;
use Modules\Tenant\Domain\Constants\TenantStatus;
use Modules\Tenant\Domain\ValueObjects\TenantIsolationKey;

final class Tenant extends Entity
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        int|string $id,
        private readonly string $uuid,
        private readonly string $code,
        private readonly string $name,
        private readonly string $slug,
        private readonly ?string $logoPath,
        private readonly bool $crossOrgTransactions,
        private readonly ?int $tenantPlanId,
        private readonly ?int $currencyId,
        private readonly string $status,
        private readonly ?string $trialEndsAt,
        private readonly ?string $subscriptionEndsAt,
        private readonly bool $isActive,
        private readonly bool $isIsolated,
        private readonly ?TenantIsolationKey $isolationKey,
        private readonly ?string $configurationScope,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (
            trim($this->uuid) === ''
            || trim($this->code) === ''
            || trim($this->name) === ''
            || trim($this->slug) === ''
        ) {
            throw new InvalidArgumentException('Tenant core identity fields cannot be empty.');
        }

        if (! TenantStatus::isValid($this->status)) {
            throw new InvalidArgumentException(sprintf('Invalid tenant status "%s".', $this->status));
        }

        if ($this->isIsolated && $this->isolationKey === null) {
            throw new InvalidArgumentException('Isolated tenant must define an isolation key.');
        }
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
