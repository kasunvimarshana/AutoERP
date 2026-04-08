<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class UserData extends BaseDto
{
    public ?string $name = null;

    public ?string $email = null;

    public ?string $password = null;

    public ?int $tenant_id = null;

    public ?string $status = null;

    public ?string $phone = null;

    public ?string $avatar_path = null;

    public ?array $preferences = null;

    public ?string $locale = null;

    public ?string $timezone = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => ['sometimes', 'email', 'max:255'],
            'password'    => ['sometimes', 'string', 'min:8'],
            'tenant_id'   => ['sometimes', 'integer'],
            'status'      => ['sometimes', 'string', 'in:active,inactive,suspended,pending'],
            'phone'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'avatar_path' => ['sometimes', 'nullable', 'string'],
            'preferences' => ['sometimes', 'nullable', 'array'],
            'locale'      => ['sometimes', 'nullable', 'string', 'max:10'],
            'timezone'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'metadata'    => ['sometimes', 'nullable', 'array'],
        ];
    }
}
