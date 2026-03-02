<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Commands;

readonly class CreatePosOrderCommand
{
    public function __construct(
        public int $tenantId,
        public int $posSessionId,
        public string $currency,
        public array $lines,
        public ?string $notes,
    ) {}
}
