<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Auth\Application\Commands\LogoutCommand;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;

/**
 * Revokes the access token associated with the current HTTP request.
 * Delegates token deletion to the repository so no Eloquent code
 * leaks into the controller layer.
 */
class LogoutHandler extends BaseHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(LogoutCommand $command): void
    {
        if ($command->bearerToken !== null) {
            $this->userRepository->revokeTokenByBearerString(
                $command->userId,
                $command->tenantId,
                $command->bearerToken,
            );
        }
    }
}
