<?php
namespace Modules\Configuration\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class SystemSetting extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $group,
        public readonly string $key,
        public readonly ?string $value,
        public readonly string $type,
        public readonly bool $isEncrypted = false,
        public readonly bool $isPublic = false,
    ) {
        parent::__construct($id);
    }
}
