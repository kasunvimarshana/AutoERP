<?php
namespace Modules\Accounting\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Account extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $parentId = null,
        public readonly string $currency = 'USD',
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {
        parent::__construct($id);
    }
}
