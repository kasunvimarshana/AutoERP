<?php
namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class AccountData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $parentId = null,
        public readonly string $currency = 'USD',
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {}
}
