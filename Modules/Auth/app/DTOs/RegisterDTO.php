<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Register Data Transfer Object
 *
 * Encapsulates user registration data with type safety and validation.
 */
class RegisterDTO extends BaseDTO
{
    /**
     * RegisterDTO constructor
     *
     * @param  string  $name  User's full name
     * @param  string  $email  User's email address
     * @param  string  $password  User's password (will be hashed)
     * @param  string|null  $role  Optional role to assign (default: 'user')
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $role = 'user'
    ) {}

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role'] ?? 'user'
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
        ];
    }
}
