<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class RegisterData extends BaseDto
{
    public int $tenant_id = 0;

    public string $email = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $password = '';

    public ?string $phone = null;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
