<?php

declare(strict_types=1);

namespace Modules\Tenant\Entities;

use InvalidArgumentException;
use Modules\Core\Support\Entity;

final class TenantPlan extends Entity
{
    /**
     * @param  array<string, mixed>  $features
     * @param  array<string, mixed>  $limits
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        int|string $id,
        private readonly string $name,
        private readonly string $slug,
        private readonly array $features,
        private readonly array $limits,
        private readonly string $billingInterval,
        private readonly bool $isActive,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (trim($this->name) === '' || trim($this->slug) === '') {
            throw new InvalidArgumentException('Tenant plan name and slug are required.');
        }

        if (! in_array($this->billingInterval, ['month', 'year'], true)) {
            throw new InvalidArgumentException('Tenant plan billing interval must be month or year.');
        }
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
