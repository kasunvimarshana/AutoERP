<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class TenantActorSnapshotFactory
{
    public function __construct(private readonly CurrentUserContextAccessorInterface $currentUser) {}

    /** @return array{id:?int,type:string,name:?string,email:?string} */
    public function current(): array
    {
        $context = $this->currentUser->current();
        if ($context === null) {
            return $this->system();
        }

        $user = $context->user();
        $firstName = trim((string) ($user->getAttribute('first_name') ?? ''));
        $lastName = trim((string) ($user->getAttribute('last_name') ?? ''));
        $name = trim($firstName.' '.$lastName);
        $email = trim((string) ($user->getAttribute('email') ?? ''));
        $platformGuard = (string) config('module-auth.platform_protected_route_guard', 'platform-api');

        return [
            'id' => $context->userId(),
            'type' => $context->guard() === $platformGuard ? 'platform_operator' : 'tenant_user',
            'name' => $name !== '' ? $name : ($email !== '' ? $email : null),
            'email' => $email !== '' ? $email : null,
        ];
    }

    /** @return array{id:null,type:string,name:null,email:null} */
    public function system(): array
    {
        return ['id' => null, 'type' => 'system', 'name' => null, 'email' => null];
    }
}
