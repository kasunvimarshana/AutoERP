<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Commands;

class DeleteCustomFieldCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
    ) {}
}
