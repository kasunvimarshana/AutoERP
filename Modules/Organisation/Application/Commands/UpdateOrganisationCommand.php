<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Commands;

final readonly class UpdateOrganisationCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?int $parentId = null,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
        public readonly ?array $meta = null,
    ) {}
}
