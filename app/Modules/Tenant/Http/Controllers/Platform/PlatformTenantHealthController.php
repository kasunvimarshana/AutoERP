<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Http\Requests\Platform\RetryPlatformHealthOperationRequest;
use Modules\Tenant\Http\Requests\Platform\RetryFailedTenantDomainsRequest;
use Modules\Tenant\Services\Platform\PlatformTenantHealthService;

final class PlatformTenantHealthController extends Controller
{
    public function __construct(private readonly PlatformTenantHealthService $health) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->health->overview()]);
    }

    public function tenant(int $tenant): JsonResponse
    {
        return response()->json(['data' => $this->health->tenant($tenant)]);
    }

    public function retryDomains(RetryFailedTenantDomainsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(['data' => [
            'requeued_count' => $this->health->retryFailedDomains(
                isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null,
                (int) ($validated['limit'] ?? 50),
                (string) $validated['reason'],
            ),
        ]]);
    }

    public function retryOutbox(RetryPlatformHealthOperationRequest $request, string $eventUuid): JsonResponse
    {
        return response()->json(['data' => [
            'requeued_count' => $this->health->retryDeadOutbox(
                $eventUuid,
                (string) $request->validated('reason'),
            ),
        ]]);
    }

    public function retryStorage(RetryPlatformHealthOperationRequest $request, int $job): JsonResponse
    {
        return response()->json(['data' => [
            'requeued_count' => $this->health->retryDeadStorage(
                $job,
                null,
                (string) $request->validated('reason'),
            ),
        ]]);
    }
}
