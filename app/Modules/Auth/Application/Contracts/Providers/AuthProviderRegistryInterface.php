<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\Providers;

use Modules\Auth\Application\Contracts\Providers\AuthProviderInterface;

interface AuthProviderRegistryInterface
{
    public function authenticationProvider(?int $tenantId, string $providerKey): ?AuthProviderInterface;

    public function tokenProvider(): TokenProviderInterface;

    public function sessionProvider(): SessionProviderInterface;

    public function ssoProvider(): SsoProviderInterface;

    public function verificationProvider(): VerificationProviderInterface;
}
