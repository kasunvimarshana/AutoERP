<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Events;

final readonly class OrganizationUnitCreated
{
    public function __construct(
        public string $aggregate,
        public int|string $id,
        public int $tenantId,
    ) {
    }
}