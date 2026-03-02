<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\Commands;

final readonly class DeleteOrganisationCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
