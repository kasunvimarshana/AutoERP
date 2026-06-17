<?php

declare(strict_types=1);

namespace Modules\User\Http\Requests\Concerns;

use Modules\User\Services\UserAuthorizationService;

trait AuthorizesUserPermission
{
    protected function canUse(string $permission): bool
    {
        return app(UserAuthorizationService::class)->canCurrent($permission);
    }
}
