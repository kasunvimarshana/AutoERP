<?php
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class UserData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $status = 'active',
    ) {}
}
