<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Http\Requests\CreateTenantDomainRequest;
use Modules\Tenant\Http\Requests\TenantVersionRequest;
use Modules\Tenant\Http\Resources\TenantDomainResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Domains\TenantDomainService;
use Modules\Core\Results\Error;

final class PlatformTenantDomainController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainService $domains,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function index(int|string $tenant): JsonResponse
    {
        $result = $this->forTenant($tenant, fn (int $tenantId): Result => $this->domains->list($tenantId));

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json([
                'data' => TenantDomainResource::collection($result->valueOrFail())->resolve(),
            ]);
    }

    public function store(
        CreateTenantDomainRequest $request,
        int|string $tenant,
    ): JsonResponse|TenantDomainResource {
        $result = $this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->create($tenantId, $request->validated()),
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : (new TenantDomainResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function requestVerification(
        TenantVersionRequest $request,
        int|string $tenant,
        int|string $tenantDomain,
    ): JsonResponse {
        $result = $this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->requestVerification(
                $tenantId,
                $tenantDomain,
                (int) $request->validated('expected_version'),
            ),
        );
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $verification = $result->valueOrFail();

        return response()->json([
            'data' => (new TenantDomainResource($verification['domain']))->resolve(),
            'challenge' => $verification['challenge'],
        ]);
    }

    public function verify(
        TenantVersionRequest $request,
        int|string $tenant,
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        return $this->domainResponse($this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->verify(
                $tenantId,
                $tenantDomain,
                (int) $request->validated('expected_version'),
            ),
        ));
    }

    public function setPrimary(
        TenantVersionRequest $request,
        int|string $tenant,
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        return $this->domainResponse($this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->setPrimary(
                $tenantId,
                $tenantDomain,
                (int) $request->validated('expected_version'),
            ),
        ));
    }

    public function disable(
        TenantVersionRequest $request,
        int|string $tenant,
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        return $this->domainResponse($this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->disable(
                $tenantId,
                $tenantDomain,
                (int) $request->validated('expected_version'),
            ),
        ));
    }

    public function destroy(
        TenantVersionRequest $request,
        int|string $tenant,
        int|string $tenantDomain,
    ): JsonResponse {
        $result = $this->forTenant(
            $tenant,
            fn (int $tenantId): Result => $this->domains->delete(
                $tenantId,
                $tenantDomain,
                (int) $request->validated('expected_version'),
            ),
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json(null, 204);
    }

    private function forTenant(int|string $tenant, callable $callback): Result
    {
        $record = $this->tenants->findById($tenant);
        if ($record === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
        }

        return $this->executionContext->runForTenant((int) $record->id(), fn (): Result => $callback((int) $record->id()));
    }

    private function domainResponse(Result $result): JsonResponse|TenantDomainResource
    {
        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantDomainResource($result->valueOrFail());
    }
}
