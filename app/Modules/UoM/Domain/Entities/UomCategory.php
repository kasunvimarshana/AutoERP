<?php
namespace Modules\UoM\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class UomCategory extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $measureType,
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {
        parent::__construct($id);
    }
}
