<?php
namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class TenantData extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $email,
        public readonly string $status = 'active',
        public readonly ?string $plan = null,
        public readonly ?array $databaseConfig = null,
        public readonly ?array $featureFlags = null,
    ) {}
}
