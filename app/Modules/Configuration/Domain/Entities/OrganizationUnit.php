<?php
namespace Modules\Configuration\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class OrganizationUnit extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly ?int $parentId = null,
        public readonly ?string $address = null,
        public readonly bool $isActive = true,
    ) {
        parent::__construct($id);
    }
}
