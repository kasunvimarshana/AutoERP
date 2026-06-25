<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Requests\OrganizationUnitLogoRequest;
use Modules\OrganizationUnit\Http\Requests\OrganizationUnitVersionRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitResource;
use Modules\OrganizationUnit\Http\Responses\OrganizationUnitApiResponder;
use Modules\OrganizationUnit\Services\Authorization\OrganizationUnitAuthorizationService;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationUnitService;

final class OrganizationUnitController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitService $units,
        private readonly OrganizationUnitAuthorizationService $authorization,
    ) {}

    public function index(ListOrganizationUnitRequest $request): JsonResponse
    {
        $this->authorize($request, OrganizationUnitPermission::VIEW);
        $filters = $request->validated();
        unset($filters['page'], $filters['per_page']);
        $result = $this->units->page($filters, $request->perPage(), $request->page());
        if ($result->isFailure()) {
            return OrganizationUnitApiResponder::error($result->errorOrFail());
        }
        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500);

        return response()->json([
            'data' => OrganizationUnitResource::collection($page->items)->resolve($request),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(ListOrganizationUnitRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::VIEW);
        $result = $this->units->get($organizationUnit);
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitRequest $request): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::CREATE);
        $result = $this->units->create($request->validated());
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : (new OrganizationUnitResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::UPDATE);
        $result = $this->units->update($organizationUnit, $request->validated());
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function activate(OrganizationUnitVersionRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::ACTIVATE);
        $result = $this->units->activate($organizationUnit, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function deactivate(OrganizationUnitVersionRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::DEACTIVATE);
        $result = $this->units->deactivate($organizationUnit, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function retire(OrganizationUnitVersionRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::RETIRE);
        $result = $this->units->retire($organizationUnit, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function replaceLogo(OrganizationUnitLogoRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::UPDATE);
        $file = $request->file('logo');
        abort_unless($file instanceof UploadedFile && is_string($file->getRealPath()), 422, 'A valid logo file is required.');
        $result = $this->units->replaceLogo(
            $organizationUnit,
            (int) $request->validated('expected_version'),
            $file->getRealPath(),
        );
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    public function removeLogo(OrganizationUnitVersionRequest $request, int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $this->authorize($request, OrganizationUnitPermission::UPDATE);
        $result = $this->units->removeLogo($organizationUnit, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitResource($result->valueOrFail());
    }

    private function authorize(ListOrganizationUnitRequest|UpsertOrganizationUnitRequest|OrganizationUnitVersionRequest|OrganizationUnitLogoRequest $request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
