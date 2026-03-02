<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Commands\LoginCommand;
use Modules\Auth\Application\Commands\LogoutCommand;
use Modules\Auth\Application\Commands\RegisterUserCommand;
use Modules\Auth\Application\Handlers\LoginHandler;
use Modules\Auth\Application\Handlers\LogoutHandler;
use Modules\Auth\Application\Handlers\RegisterUserHandler;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\Entities\User;

/**
 * Service orchestrating all authentication-related operations.
 *
 * Controllers must interact with the auth domain exclusively through this
 * service. Write operations are delegated to the appropriate command
 * handlers; read operations are fulfilled directly via the repository
 * contract, keeping the controller layer free of business logic.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RegisterUserHandler $registerUserHandler,
        private readonly LoginHandler $loginHandler,
        private readonly LogoutHandler $logoutHandler,
    ) {}

    /**
     * Register a new user and return the persisted entity.
     */
    public function registerUser(RegisterUserCommand $command): User
    {
        return $this->registerUserHandler->handle($command);
    }

    /**
     * Authenticate a user and return the user entity together with its token.
     *
     * @return array{user: User, token: string}
     */
    public function login(LoginCommand $command): array
    {
        return $this->loginHandler->handle($command);
    }

    /**
     * Revoke the bearer token for the authenticated user.
     */
    public function logout(LogoutCommand $command): void
    {
        $this->logoutHandler->handle($command);
    }

    /**
     * Find a single user by their identifier within the given tenant.
     *
     * Read operations are fulfilled directly via the repository contract
     * without delegating to a handler, consistent with other services.
     */
    public function findUserById(int $userId, int $tenantId): ?User
    {
        return $this->userRepository->findById($userId, $tenantId);
    }
}
