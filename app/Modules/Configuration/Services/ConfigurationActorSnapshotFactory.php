<?php

declare(strict_types=1);

namespace Modules\Configuration\Services;

use Modules\Configuration\Constants\ConfigurationActorType;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class ConfigurationActorSnapshotFactory
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    /** @return array{type:string,id:?int,name:?string,email:?string} */
    public function current(): array
    {
        $context = $this->currentUser->current();
        if ($context === null) {
            return [
                'type' => ConfigurationActorType::SYSTEM,
                'id' => null,
                'name' => null,
                'email' => null,
            ];
        }

        $user = $context->user();
        $firstName = trim((string) ($user->getAttribute('first_name') ?? ''));
        $lastName = trim((string) ($user->getAttribute('last_name') ?? ''));
        $name = trim($firstName.' '.$lastName);
        $email = trim((string) ($user->getAttribute('email') ?? ''));
        $platformOperator = $context->guard() === (string) config(
            'module-auth.platform_protected_route_guard',
            'platform-api',
        );

        return [
            'type' => $platformOperator
                ? ConfigurationActorType::PLATFORM_OPERATOR
                : ConfigurationActorType::TENANT_USER,
            'id' => $context->userId(),
            'name' => $name !== '' ? $name : ($email !== '' ? $email : 'User #'.$context->userId()),
            'email' => $email !== '' ? $email : null,
        ];
    }
}
