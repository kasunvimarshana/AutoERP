<?php
namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ProductCategoryData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?int $parentId = null,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
    ) {}
}
