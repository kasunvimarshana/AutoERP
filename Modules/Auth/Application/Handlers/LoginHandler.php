<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Auth\Application\Commands\LoginCommand;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;

class LoginHandler extends BaseHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @return array{user: \Modules\Auth\Domain\Entities\User, token: string}
     */
    public function handle(LoginCommand $command): array
    {
        $userEntity = $this->userRepository->findByEmail($command->email, $command->tenantId);

        if ($userEntity === null) {
            throw new \DomainException('Invalid credentials.');
        }

        if (! $this->userRepository->verifyPassword($userEntity->id, $command->tenantId, $command->password)) {
            throw new \DomainException('Invalid credentials.');
        }

        $token = $this->userRepository->createAuthToken($userEntity->id, $command->tenantId, $command->deviceName);

        return [
            'user' => $userEntity,
            'token' => $token,
        ];
    }
}
