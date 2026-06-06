<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Providers;

interface AuthProviderRegistryInterface
{
    public function authenticationProvider(?int $tenantId, string $providerKey): ?AuthProviderInterface;

    public function tokenProvider(): TokenProviderInterface;

    public function sessionProvider(): SessionProviderInterface;

    public function ssoProvider(): SsoProviderInterface;

    public function verificationProvider(): VerificationProviderInterface;
}
