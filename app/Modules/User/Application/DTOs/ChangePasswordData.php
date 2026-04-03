<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ChangePasswordData extends BaseDto
{
    public string $current_password;

    public string $password;

    public string $password_confirmation;

    public function rules(): array
    {
        return [
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }
}
