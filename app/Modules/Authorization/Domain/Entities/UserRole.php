<?php
namespace Modules\Authorization\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class UserRole extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $userId,
        public readonly int $roleId,
    ) {
        parent::__construct($id);
    }
}
