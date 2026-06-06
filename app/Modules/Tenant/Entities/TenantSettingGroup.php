<?php

declare(strict_types=1);

namespace Modules\Tenant\Entities;

use InvalidArgumentException;
use Modules\Core\Entities\Entity;

final class TenantSettingGroup extends Entity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int|string $tenantId,
        private readonly string $key,
        private readonly ?string $value,
        private readonly int|string|null $parentId,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (trim((string) $this->tenantId) === '' || trim($this->key) === '') {
            throw new InvalidArgumentException('Tenant setting group requires tenant id and key.');
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
