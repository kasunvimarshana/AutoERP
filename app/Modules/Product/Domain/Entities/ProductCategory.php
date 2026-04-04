<?php
namespace Modules\Product\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class ProductCategory extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?int $parentId = null,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
    ) {
        parent::__construct($id);
    }
}
