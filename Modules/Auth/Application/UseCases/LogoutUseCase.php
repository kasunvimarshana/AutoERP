<?php
namespace Modules\Auth\Application\UseCases;
use Modules\Auth\Domain\Contracts\AuthServiceInterface;
use Modules\Auth\Domain\Events\UserLoggedOut;
class LogoutUseCase
{
    public function __construct(private AuthServiceInterface $authService) {}
    public function execute(array $data): void
    {
        $this->authService->logout($data['user_id']);
        UserLoggedOut::dispatch($data['user_id']);
    }
}
