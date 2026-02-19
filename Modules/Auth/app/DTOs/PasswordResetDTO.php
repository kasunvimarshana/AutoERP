<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Password Reset Data Transfer Object
 *
 * Encapsulates password reset data with type safety.
 */
class PasswordResetDTO extends BaseDTO
{
    /**
     * PasswordResetDTO constructor
     *
     * @param  string  $email  User's email address
     * @param  string  $token  Password reset token
     * @param  string  $password  New password
     * @param  string  $passwordConfirmation  Password confirmation
     */
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password,
        public readonly string $passwordConfirmation
    ) {}

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            email: $data['email'],
            token: $data['token'],
            password: $data['password'],
            passwordConfirmation: $data['password_confirmation']
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
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ];
    }
}
