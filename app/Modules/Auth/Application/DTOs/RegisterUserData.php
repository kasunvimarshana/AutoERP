<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class RegisterUserData extends BaseDto
{
    public string $name;

    public string $email;

    public string $password;

    public ?int $tenant_id = null;

    public ?string $phone = null;

    public ?string $locale = null;

    public ?string $timezone = null;

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'password'  => ['required', 'string', 'min:8'],
            'tenant_id' => ['nullable', 'integer'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'locale'    => ['nullable', 'string', 'max:10'],
            'timezone'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
