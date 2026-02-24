<?php
namespace Modules\Auth\Infrastructure\Services;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\ValueObjects\AuthToken;
use Modules\Auth\Domain\ValueObjects\LoginCredentials;
use Modules\User\Infrastructure\Models\UserModel;
class SanctumAuthService implements AuthServiceInterface
{
    public function login(LoginCredentials $credentials): AuthToken
    {
        $user = UserModel::where('email', $credentials->email)->first();
        if (! $user || ! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }
        $user->tokens()->where('name', $credentials->deviceName)->delete();
        $token = $user->createToken($credentials->deviceName ?? 'web');
        return new AuthToken(
            accessToken: $token->plainTextToken,
            tokenType: 'Bearer',
            expiresIn: config('auth_module.access_token_ttl', 900),
        );
    }
    public function logout(string $userId): void
    {
        $user = UserModel::find($userId);
        $user?->currentAccessToken()?->delete();
    }
    public function refresh(string $refreshToken): AuthToken
    {
        throw new \RuntimeException('Use /login to obtain a new token.');
    }
}
