<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Login Data Transfer Object
 *
 * Encapsulates user login credentials with type safety.
 */
class LoginDTO extends BaseDTO
{
    /**
     * LoginDTO constructor
     *
     * @param  string  $email  User's email address
     * @param  string  $password  User's password
     * @param  bool  $revokeOtherTokens  Whether to revoke other active tokens
     */
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $revokeOtherTokens = false
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
            password: $data['password'],
            revokeOtherTokens: $data['revoke_other_tokens'] ?? false
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
            'password' => $this->password,
            'revoke_other_tokens' => $this->revokeOtherTokens,
        ];
    }
}
