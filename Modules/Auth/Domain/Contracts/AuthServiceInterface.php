<?php
namespace Modules\Auth\Domain\Contracts;
use Modules\Auth\Domain\ValueObjects\AuthToken;
use Modules\Auth\Domain\ValueObjects\LoginCredentials;
interface AuthServiceInterface
{
    public function login(LoginCredentials $credentials): AuthToken;
    public function logout(string $userId): void;
    public function refresh(string $refreshToken): AuthToken;
}
