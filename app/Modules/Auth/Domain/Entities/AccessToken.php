<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

class AccessToken
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $tokenType,
        private readonly int $expiresIn,
        private readonly ?string $refreshToken = null,
        private readonly array $scopes = [],
    ) {}

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
            'scopes' => $this->scopes,
        ];
    }
}
