<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Events;

final readonly class OrganizationUnitUpdated
{
    public function __construct(
        public string $aggregate,
        public int|string $id,
        public int $tenantId,
    ) {
    }
}