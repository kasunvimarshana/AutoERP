<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

final class Tenant
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $status,
        public readonly ?string $domain,
        public readonly string $planCode,
        public readonly string $currency,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
