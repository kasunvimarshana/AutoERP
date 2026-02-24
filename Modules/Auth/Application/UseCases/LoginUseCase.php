<?php
namespace Modules\Auth\Application\UseCases;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Domain\ValueObjects\AuthToken;
use Modules\Auth\Domain\ValueObjects\LoginCredentials;
class LoginUseCase
{
    public function __construct(private AuthServiceInterface $authService) {}
    public function execute(array $data): AuthToken
    {
        $credentials = new LoginCredentials(
            email: $data['email'],
            password: $data['password'],
            deviceName: $data['device_name'] ?? 'web',
        );
        $token = $this->authService->login($credentials);
        UserLoggedIn::dispatch($credentials->email, request()->ip() ?? '');
        return $token;
    }
}
