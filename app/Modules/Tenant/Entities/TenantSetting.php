<?php

declare(strict_types=1);

namespace Modules\Tenant\Entities;

use InvalidArgumentException;
use Modules\Core\Entities\Entity;

final class TenantSetting extends Entity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int|string $tenantId,
        private readonly int|string $groupId,
        private readonly string $key,
        private readonly ?string $value,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (
            trim((string) $this->tenantId) === ''
            || trim((string) $this->groupId) === ''
            || trim($this->key) === ''
        ) {
            throw new InvalidArgumentException('Tenant setting requires tenant id, group id, and key.');
        }
    }

    public function tenantId(): int|string
    {
        return $this->tenantId;
    }

    public function key(): string
    {
        return $this->key;
    }
}
