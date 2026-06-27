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
     * @return array{
     *     ready:bool,
     *     mode:string,
     *     message:string,
     *     local_fallback:array{
     *         supported:bool,
     *         enabled:bool,
     *         configured_tenant_code:?string,
     *         matches_tenant:bool
     *     }
     * }
     */
    public function inspect(string $tenantCode, array|DataRecord|null $primaryDomain): array
    {
        $localFallback = $this->localFallbackDetails($tenantCode);

        if ($primaryDomain !== null && $this->domains->isReady($primaryDomain)) {
            return [
                'ready' => true,
                'mode' => self::MODE_VERIFIED_DOMAIN,
                'message' => 'The verified primary tenant domain is operational.',
                'local_fallback' => $localFallback,
            ];
        }

        if ($localFallback['matches_tenant']) {
            return [
                'ready' => true,
                'mode' => self::MODE_LOCAL_FALLBACK,
                'message' => 'Local/testing tenant routing is explicitly configured for this tenant.',
                'local_fallback' => $localFallback,
            ];
        }

        return [
            'ready' => false,
            'mode' => self::MODE_UNAVAILABLE,
            'message' => $this->app->environment(['local', 'testing'])
                ? 'Enable and configure the local tenant fallback, or verify a public tenant domain.'
                : 'Verify a public primary tenant domain including ownership, routing, TLS, and reachability.',
            'local_fallback' => $localFallback,
        ];
    }

    /**
     * @return array{
     *     supported:bool,
     *     enabled:bool,
     *     configured_tenant_code:?string,
     *     matches_tenant:bool
     * }
     */
    private function localFallbackDetails(string $tenantCode): array
    {
        $supported = $this->app->environment(['local', 'testing']);
        $enabled = $supported && (bool) config('tenant.resolution.local_fallback_enabled', false);

        $configuredCode = strtoupper(trim((string) config('tenant.resolution.local_fallback_tenant_code', '')));
        $matchesTenant = $enabled
            && $configuredCode !== ''
            && $configuredCode === strtoupper(trim($tenantCode));

        return [
            'supported' => $supported,
            'enabled' => $enabled,
            'configured_tenant_code' => $supported && $configuredCode !== '' ? $configuredCode : null,
            'matches_tenant' => $matchesTenant,
        ];
    }
}
