<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Hosts;

use Illuminate\Contracts\Foundation\Application;

final class PlatformHostPolicy
{
    private const LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '::1'];

    public function __construct(private readonly Application $app) {}

    public function normalize(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $host = strtolower(rtrim(trim((string) $value), '.'));

        return $host === '' ? null : $host;
    }

    public function isCentralHost(mixed $value): bool
    {
        $host = $this->normalize($value);
        if ($host === null) {
            return false;
        }

        if (in_array($host, $this->configuredCentralHosts(), true)) {
            return true;
        }

        return $this->app->environment(['local', 'testing'])
            && in_array($host, self::LOOPBACK_HOSTS, true);
    }

    /** @return list<string> */
    public function configuredCentralHosts(): array
    {
        $configured = config('tenant.resolution.central_hosts', []);
        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $host): ?string => $this->normalize($host),
            $configured,
        ))));
    }
}
