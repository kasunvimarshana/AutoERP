<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Handlers;

use Illuminate\Auth\AuthenticationException;
use Modules\Auth\Application\Commands\LoginCommand;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\ValueObjects\Email;
use Modules\Auth\Infrastructure\Models\User as UserModel;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Handle the login command.
     *
     * @return array{token: string, token_type: string, expires_in: int, user: array}
     *
     * @throws AuthenticationException
     */
    public function handle(LoginCommand $command): array
    {
        $email  = new Email($command->email);
        $entity = $this->users->findActiveByEmail($email);

        if ($entity === null || ! password_verify($command->password, (string) $entity->getPasswordHash())) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        // JWT requires an Eloquent model that implements JWTSubject — load by ID.
        /** @var UserModel $model */
        $model = UserModel::findOrFail($entity->getId());

        /** @var string $token */
        $token = JWTAuth::fromUser($model);

        $this->users->updateLastLoginAt($entity->getId());

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl', 60) * 60,
            'user'       => [
                'id'              => $entity->getId(),
                'name'            => $entity->getName(),
                'email'           => $entity->getEmail()->getValue(),
                'role'            => $entity->getRole(),
                'tenant_id'       => $entity->getTenantId(),
                'organisation_id' => $entity->getOrganisationId(),
            ],
        ];
    }
}
