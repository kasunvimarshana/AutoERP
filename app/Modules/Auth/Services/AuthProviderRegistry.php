<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Contracts\Container\Container;
use Modules\Auth\Contracts\Providers\AuthProviderInterface;
use Modules\Auth\Contracts\Providers\AuthProviderRegistryInterface;
use Modules\Auth\Contracts\Providers\SessionProviderInterface;
use Modules\Auth\Contracts\Providers\SsoProviderInterface;
use Modules\Auth\Contracts\Providers\TokenProviderInterface;
use Modules\Auth\Contracts\Providers\VerificationProviderInterface;
use Modules\Auth\Repositories\AuthProviderRepositoryInterface;

final class AuthProviderRegistry implements AuthProviderRegistryInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly AuthProviderRepositoryInterface $providers,
        private readonly DatabaseTokenProvider $tokenProvider,
        private readonly DatabaseSessionProvider $sessionProvider,
        private readonly DatabaseSsoProvider $ssoProvider,
        private readonly DatabaseVerificationProvider $verificationProvider,
    ) {}

    public function authenticationProvider(?int $tenantId, string $providerKey): ?AuthProviderInterface
    {
        $provider = $this->providers->findActiveByKey($tenantId, $providerKey);
        if ($provider === null) {
            return null;
        }

        $driver = trim((string) $provider->get('driver', 'internal'));
        $driverMap = config('module-auth.provider_drivers', [
            'internal' => InternalAuthenticationProvider::class,
        ]);

        if (! is_array($driverMap)) {
            return null;
        }

        $providerClass = $driverMap[$driver] ?? null;
        if (! is_string($providerClass) || $providerClass === '') {
            return null;
        }

        $resolved = $this->container->make($providerClass);
        if ($resolved instanceof AuthProviderInterface) {
            return $resolved;
        }

        return null;
    }

    public function tokenProvider(): TokenProviderInterface
    {
        return $this->tokenProvider;
    }

    public function sessionProvider(): SessionProviderInterface
    {
        return $this->sessionProvider;
    }

    public function ssoProvider(): SsoProviderInterface
    {
        return $this->ssoProvider;
    }

    public function verificationProvider(): VerificationProviderInterface
    {
        return $this->verificationProvider;
    }
}
