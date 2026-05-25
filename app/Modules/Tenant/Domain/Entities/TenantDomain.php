<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use InvalidArgumentException;
use Modules\Core\Domain\Entities\Entity;

final class TenantDomain extends Entity
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int|string $tenantId,
        private readonly string $domain,
        private readonly bool $isPrimary,
        private readonly bool $isVerified,
        private readonly ?string $verifiedAt,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (trim((string) $this->tenantId) === '' || trim($this->domain) === '') {
            throw new InvalidArgumentException('Tenant domain requires tenant id and domain value.');
        }

        if ($this->isVerified && $this->verifiedAt === null) {
            throw new InvalidArgumentException('Verified tenant domain requires verified timestamp.');
        }
    }

    public function tenantId(): int|string
    {
        return $this->tenantId;
    }

    public function domain(): string
    {
        return $this->domain;
    }
}
