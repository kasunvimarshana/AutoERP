<?php
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class UpdateProfileData extends BaseDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?array $preferences = null,
    ) {}
}
