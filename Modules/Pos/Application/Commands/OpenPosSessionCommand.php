<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Commands;

readonly class OpenPosSessionCommand
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $openingFloat,
        public string $currency,
        public ?string $notes,
    ) {}
}
