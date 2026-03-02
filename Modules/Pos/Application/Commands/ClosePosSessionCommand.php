<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Commands;

readonly class ClosePosSessionCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $closingFloat,
        public ?string $notes,
    ) {}
}
