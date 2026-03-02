<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Commands;

final readonly class CreateAccountCommand
{
    public function __construct(
        public int $tenantId,
        public ?int $parentId,
        public string $code,
        public string $name,
        public string $type,
        public ?string $description,
        public string $openingBalance = '0.0000',
    ) {}
}
