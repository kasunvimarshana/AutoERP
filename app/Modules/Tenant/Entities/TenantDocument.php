<?php

declare(strict_types=1);

namespace Modules\Tenant\Entities;

use InvalidArgumentException;
use Modules\Core\Support\Entity;

final class TenantDocument extends Entity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int|string $tenantId,
        private readonly string $name,
        private readonly string $filePath,
        private readonly ?string $mimeType,
        private readonly ?int $size,
        private readonly ?string $type,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if (trim((string) $this->tenantId) === '' || trim($this->name) === '' || trim($this->filePath) === '') {
            throw new InvalidArgumentException('Tenant document requires tenant id, name, and file path.');
        }
    }

    public function tenantId(): int|string
    {
        return $this->tenantId;
    }
}
