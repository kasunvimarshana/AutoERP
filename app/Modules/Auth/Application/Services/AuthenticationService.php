<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\AuthenticationServiceInterface;
use Modules\Auth\Application\Contracts\TenantContextResolverInterface;
use Modules\Auth\Application\Contracts\TokenServiceInterface;
use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\InvalidCredentialsException;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
        private readonly TenantContextResolverInterface $tenantContextResolver,
    ) {}

    public function authenticate(string $email, string $password): AccessToken
    {
        $credentials = ['email' => $email, 'password' => $password];
        $tenantId = $this->tenantContextResolver->resolveTenantId();
        if ($tenantId !== null) {
            $credentials['tenant_id'] = $tenantId;
        }

        if (! Auth::attempt($credentials)) {
            throw new InvalidCredentialsException;
        }

        /** @var Authenticatable $user */
        $user = Auth::user();

        return $this->tokenService->issueToken($user->getAuthIdentifier());
    }

    public function invalidate(int $userId): bool
    {
        return $this->tokenService->revokeCurrentToken($userId);
    }
}
