<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Result;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Requests\CreateTenantDomainRequest;
use Modules\Tenant\Http\Requests\ListTenantDomainRequest;
use Modules\Tenant\Http\Requests\TenantVersionRequest;
use Modules\Tenant\Http\Resources\TenantDomainResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Domains\TenantDomainService;
use Modules\Tenant\Services\TenantAuthorizationService;

final class TenantDomainController extends Controller
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
        private readonly CurrentTenantContextAccessorInterface $context,
        private readonly TenantDomainService $domains,
    ) {}

    public function index(ListTenantDomainRequest $request): JsonResponse
    {
        $this->requirePermission(TenantPermission::DOMAINS_VIEW);

        $result = $this->domains->list($this->tenantId(), $request->validated());
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }
        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500, 'Unexpected tenant domain list response.');

        return response()->json([
            'data' => TenantDomainResource::collection($page->items)->resolve($request),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(int|string $tenantDomain): JsonResponse|TenantDomainResource
    {
        $this->requirePermission(TenantPermission::DOMAINS_VIEW);

        return $this->domainResponse(
            $this->domains->get($this->tenantId(), $tenantDomain),
        );
    }

    public function store(
        CreateTenantDomainRequest $request,
    ): JsonResponse|TenantDomainResource {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        $result = $this->domains->create(
            $this->tenantId(),
            $request->validated(),
        );
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        return (new TenantDomainResource($result->valueOrFail()))
            ->response()
            ->setStatusCode(201);
    }

    public function requestVerification(
        TenantVersionRequest $request,
        int|string $tenantDomain,
    ): JsonResponse {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        $result = $this->domains->requestVerification(
            $this->tenantId(),
            $tenantDomain,
            (int) $request->validated('expected_version'),
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
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        return $this->domainResponse($this->domains->verify(
            $this->tenantId(),
            $tenantDomain,
            (int) $request->validated('expected_version'),
        ));
    }

    public function setPrimary(
        TenantVersionRequest $request,
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        return $this->domainResponse($this->domains->setPrimary(
            $this->tenantId(),
            $tenantDomain,
            (int) $request->validated('expected_version'),
        ));
    }

    public function disable(
        TenantVersionRequest $request,
        int|string $tenantDomain,
    ): JsonResponse|TenantDomainResource {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        return $this->domainResponse($this->domains->disable(
            $this->tenantId(),
            $tenantDomain,
            (int) $request->validated('expected_version'),
        ));
    }

    public function destroy(
        TenantVersionRequest $request,
        int|string $tenantDomain,
    ): JsonResponse {
        $this->requirePermission(TenantPermission::DOMAINS_MANAGE);

        $result = $this->domains->delete(
            $this->tenantId(),
            $tenantDomain,
            (int) $request->validated('expected_version'),
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json(null, 204);
    }

    private function domainResponse(Result $result): JsonResponse|TenantDomainResource
    {
        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantDomainResource($result->valueOrFail());
    }

    private function tenantId(): int
    {
        return (int) $this->context->requireCurrent()->tenantId();
    }

    private function requirePermission(string $permission): void
    {
        abort_unless(
            $this->authorization->allows($permission),
            403,
            'You are not authorized to perform this action.',
        );
    }
}
