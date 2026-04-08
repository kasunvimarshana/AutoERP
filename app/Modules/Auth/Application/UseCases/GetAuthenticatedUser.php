<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class GetAuthenticatedUser
{
    /**
     * Return the currently authenticated user.
     */
    public function execute(): ?Authenticatable
    {
        return Auth::user();
    }
}
