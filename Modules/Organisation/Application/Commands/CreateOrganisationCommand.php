<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Commands;

final readonly class CreateOrganisationCommand
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $type,
        public readonly string $name,
        public readonly string $code,
        public readonly ?int $parentId = null,
        public readonly ?string $description = null,
        public readonly ?array $meta = null,
    ) {}
}
