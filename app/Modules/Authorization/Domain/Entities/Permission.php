<?php
namespace Modules\Authorization\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Permission extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly string $name,
        public readonly string $guardName = 'api',
        public readonly ?string $description = null,
    ) {
        parent::__construct($id);
    }
}
