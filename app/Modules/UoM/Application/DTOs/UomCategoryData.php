<?php
namespace Modules\UoM\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class UomCategoryData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $measureType,
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {}
}
