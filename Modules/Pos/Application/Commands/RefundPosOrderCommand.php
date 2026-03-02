<?php

declare(strict_types=1);

namespace Modules\Pos\Application\Commands;

readonly class RefundPosOrderCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $refundAmount,
        public string $method,
        public ?string $notes,
    ) {}
}
