<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class UpdateStorefrontOrderStatusCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $status,
    ) {}
}
