<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class DeleteProductCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}
}
