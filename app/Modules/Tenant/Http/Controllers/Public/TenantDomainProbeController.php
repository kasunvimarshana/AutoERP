<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainProbe;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;

final class TenantDomainProbeController extends Controller
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly PlatformHostPolicy $hosts,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $host = $this->hosts->normalize($request->getHost());
        $token = trim((string) $request->header(TenantDomainProbe::HEADER));
        if ($host === null || $token === '') {
            abort(404);
        }

        $domain = $this->domains->findByDomainFromControlPlane($host);
        if (
            $domain === null
            || $domain->get('status') === TenantDomainStatus::DISABLED
            || $domain->get('ownership_status') !== TenantDomainOwnershipStatus::VERIFIED
        ) {
            abort(404);
        }

        $expectedHash = trim((string) $domain->get('operational_probe_token_hash'));
        if ($expectedHash === '' || ! hash_equals($expectedHash, hash('sha256', $token))) {
            abort(404);
        }

        return response()->json([
            'ready' => true,
            'domain' => $host,
            'tenant_id' => (int) $domain->require('tenant_id'),
        ]);
    }
}
