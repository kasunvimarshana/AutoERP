<?php
namespace Modules\Configuration\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class SystemSettingData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $group,
        public readonly string $key,
        public readonly ?string $value,
        public readonly string $type = 'string',
        public readonly bool $isEncrypted = false,
        public readonly bool $isPublic = false,
    ) {}
}
