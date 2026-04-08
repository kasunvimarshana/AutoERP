<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Entities;

/**
 * Supplier domain entity.
 */
class Supplier
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $currencyCode,
        public readonly float $balance,
        public readonly string $status,
    ) {}
}
