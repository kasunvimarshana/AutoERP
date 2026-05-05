<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Domain\Events;

use Modules\Core\Domain\Events\DomainEvent;

final class OrganizationUnitDeleted extends DomainEvent
{
    public function __construct(
        public readonly int $organizationUnitId,
        public readonly int $tenantId,
        public readonly string $name,
    ) {}
}
