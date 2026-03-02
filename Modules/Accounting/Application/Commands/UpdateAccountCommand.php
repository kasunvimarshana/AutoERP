<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Commands;

final readonly class UpdateAccountCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public string $status,
    ) {}
}
