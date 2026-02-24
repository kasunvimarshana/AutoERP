<?php
namespace Modules\Auth\Domain\ValueObjects;
final class AuthToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly ?string $refreshToken = null,
    ) {}
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
