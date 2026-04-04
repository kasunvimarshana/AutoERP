<?php
namespace Modules\Pricing\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class TaxGroupData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {}
}
