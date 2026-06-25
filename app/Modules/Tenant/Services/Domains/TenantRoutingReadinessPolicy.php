<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use Illuminate\Contracts\Foundation\Application;
use Modules\Core\DTOs\DataRecord;

final class TenantRoutingReadinessPolicy
{
    public const MODE_VERIFIED_DOMAIN = 'verified_domain';
    public const MODE_LOCAL_FALLBACK = 'local_fallback';
    public const MODE_UNAVAILABLE = 'unavailable';

    public function __construct(
        private readonly Application $app,
        private readonly TenantDomainReadinessPolicy $domains,
    ) {}

    /**
     * @param array<string, mixed>|DataRecord|null $primaryDomain
     * @return array{ready:bool,mode:string,message:string}
     */
    public function inspect(string $tenantCode, array|DataRecord|null $primaryDomain): array
    {
        if ($primaryDomain !== null && $this->domains->isReady($primaryDomain)) {
            return [
                'ready' => true,
                'mode' => self::MODE_VERIFIED_DOMAIN,
                'message' => 'The verified primary tenant domain is operational.',
            ];
        }

        if ($this->localFallbackMatches($tenantCode)) {
            return [
                'ready' => true,
                'mode' => self::MODE_LOCAL_FALLBACK,
                'message' => 'Local/testing tenant routing is explicitly configured for this tenant.',
            ];
        }

        return [
            'ready' => false,
            'mode' => self::MODE_UNAVAILABLE,
            'message' => $this->app->environment(['local', 'testing'])
                ? 'Enable and configure the local tenant fallback, or verify a public tenant domain.'
                : 'Verify a public primary tenant domain including ownership, routing, TLS, and reachability.',
        ];
    }

    private function localFallbackMatches(string $tenantCode): bool
    {
        if (! $this->app->environment(['local', 'testing'])
            || ! (bool) config('tenant.resolution.local_fallback_enabled', false)
        ) {
            return false;
        }

        $configuredCode = strtoupper(trim((string) config('tenant.resolution.local_fallback_tenant_code', '')));

        return $configuredCode !== '' && $configuredCode === strtoupper(trim($tenantCode));
    }
}
