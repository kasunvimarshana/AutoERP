<?php
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ChangePasswordData extends BaseDTO
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {}
}
