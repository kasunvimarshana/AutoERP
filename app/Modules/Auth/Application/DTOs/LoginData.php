<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class LoginData extends BaseDto
{
    public string $email;

    public string $password;

    public bool $remember = false;

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
