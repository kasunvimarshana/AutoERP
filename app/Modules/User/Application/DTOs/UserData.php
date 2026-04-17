<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UserData extends BaseDto
{
    public int $tenant_id;

    public string $email;

    public string $first_name;

    public string $last_name;

    public ?string $phone;

    public ?array $address;

    public ?array $preferences;

    public ?bool $active;

    public ?string $avatar;

    public ?array $roles; // role IDs

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'email' => 'required|email|unique:users,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|array',
            'preferences' => 'nullable|array',
            'active' => 'boolean',
            'avatar' => 'nullable|string|max:2048',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ];
    }
}
