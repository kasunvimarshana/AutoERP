<?php

namespace App\Infrastructure\Tenant;

use App\Domain\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantResolver
{
    /**
     * Ordered list of drivers to try when resolving a tenant.
     */
    private array $drivers;

    public function __construct()
    {
        $this->drivers = config('tenant.identification_drivers', ['subdomain', 'header', 'jwt', 'body']);
    }

    /**
     * Resolve a tenant from the given request using configured drivers.
     */
    public function resolve(Request $request): ?Tenant
    {
        foreach ($this->drivers as $driver) {
            $tenant = match ($driver) {
                'subdomain' => $this->resolveFromSubdomain($request),
                'header'    => $this->resolveFromHeader($request),
                'jwt'       => $this->resolveFromJwt($request),
                'body'      => $this->resolveFromBody($request),
                'path'      => $this->resolveFromPath($request),
                default     => null,
            };

            if ($tenant !== null) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Extract the tenant identifier string from the request (for cache keys).
     */
    public function extractIdentifier(Request $request): ?string
    {
        // Try each driver in order and return the first non-null identifier
        $id = $this->extractFromHeader($request)
            ?? $this->extractFromSubdomain($request)
            ?? $this->extractFromBody($request)
            ?? $this->extractFromJwt($request)
            ?? $this->extractFromPath($request);

        return $id ? 'tenant_id_' . $id : null;
    }

    // -----------------------------------------------------------------------
    // Driver Implementations
    // -----------------------------------------------------------------------

    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        $subdomain = $this->extractFromSubdomain($request);

        if ($subdomain === null) {
            return null;
        }

        // Skip central domains
        $centralDomains = config('tenant.central_domains', []);
        $host           = $request->getHost();

        if (in_array($host, $centralDomains, true)) {
            return null;
        }

        return Tenant::where('subdomain', $subdomain)->where('status', 'active')->first();
    }

    private function resolveFromHeader(Request $request): ?Tenant
    {
        $tenantId = $this->extractFromHeader($request);

        if ($tenantId === null) {
            return null;
        }

        // Try by UUID (id)
        if (\Ramsey\Uuid\Uuid::isValid($tenantId)) {
            return Tenant::find($tenantId);
        }

        // Try by subdomain
        return Tenant::where('subdomain', $tenantId)->first();
    }

    private function resolveFromJwt(Request $request): ?Tenant
    {
        $tenantId = $this->extractFromJwt($request);

        if ($tenantId === null) {
            return null;
        }

        return Tenant::find($tenantId);
    }

    private function resolveFromBody(Request $request): ?Tenant
    {
        $tenantId = $this->extractFromBody($request);

        if ($tenantId === null) {
            return null;
        }

        if (\Ramsey\Uuid\Uuid::isValid($tenantId)) {
            return Tenant::find($tenantId);
        }

        return Tenant::where('subdomain', $tenantId)->first();
    }

    private function resolveFromPath(Request $request): ?Tenant
    {
        $tenantId = $this->extractFromPath($request);

        if ($tenantId === null) {
            return null;
        }

        return Tenant::find($tenantId);
    }

    // -----------------------------------------------------------------------
    // Extractors (return raw identifier strings)
    // -----------------------------------------------------------------------

    private function extractFromSubdomain(Request $request): ?string
    {
        $host       = $request->getHost();
        $baseDomain = config('tenant.base_domain', 'saas.local');

        // Strip the base domain to get the subdomain
        if (!str_ends_with($host, '.' . $baseDomain)) {
            return null;
        }

        $subdomain = str_replace('.' . $baseDomain, '', $host);

        return !empty($subdomain) ? $subdomain : null;
    }

    private function extractFromHeader(Request $request): ?string
    {
        $header = config('tenant.header', 'X-Tenant-ID');
        return $request->header($header);
    }

    private function extractFromJwt(Request $request): ?string
    {
        $bearer = $request->bearerToken();

        if ($bearer === null) {
            return null;
        }

        try {
            // JWT tokens are base64-encoded JSON — decode the payload (second segment)
            $parts = explode('.', $bearer);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_pad(
                strtr($parts[1], '-_', '+/'),
                (int) (strlen($parts[1]) + 4 - (strlen($parts[1]) % 4)),
                '='
            )), true);

            return $payload['tid'] ?? $payload['tenant_id'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractFromBody(Request $request): ?string
    {
        return $request->input('tenant_id');
    }

    private function extractFromPath(Request $request): ?string
    {
        // Matches /api/v1/tenant/{tenant_id}/...
        if (preg_match('#/tenant/([^/]+)#', $request->getPathInfo(), $matches)) {
            return $matches[1];
        }

        return null;
    }
}
