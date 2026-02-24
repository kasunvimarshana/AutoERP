<?php
namespace Modules\Auth\Application\UseCases;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\Events\TokenRefreshed;
use Modules\Auth\Domain\ValueObjects\AuthToken;
class RefreshTokenUseCase
{
    public function __construct(private AuthServiceInterface $authService) {}
    public function execute(array $data): AuthToken
    {
        $token = $this->authService->refresh($data['refresh_token']);
        TokenRefreshed::dispatch($data['user_id'] ?? '');
        return $token;
    }
}
