<?php

namespace Modules\OrganizationUnit\Infrastructure\Http\Controllers;

use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\MoveOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitData;
use Modules\OrganizationUnit\Application\DTOs\MoveOrganizationUnitData;
use Modules\OrganizationUnit\Infrastructure\Http\Requests\MoveOrganizationUnitRequest;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitResource;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitCollection;
use Modules\OrganizationUnit\Infrastructure\Http\Resources\OrganizationUnitTreeResource;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrganizationUnitController extends BaseController
{
    public function __construct(
        CreateOrganizationUnitServiceInterface $createService,
        protected UpdateOrganizationUnitServiceInterface $updateService,
        protected DeleteOrganizationUnitServiceInterface $deleteService,
        protected MoveOrganizationUnitServiceInterface $moveService,
        protected OrganizationUnitRepositoryInterface $orgUnitRepository
    ) {
        parent::__construct($createService, OrganizationUnitResource::class, OrganizationUnitData::class);
    }

    public function index(Request $request): OrganizationUnitCollection
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $filters = $request->only(['name', 'code', 'parent_id']);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $sort = $request->input('sort');
        $include = $request->input('include');

        $units = $this->service->list($filters, $perPage, $page, $sort, $include);
        return new OrganizationUnitCollection($units);
    }

    public function store(Request $request): OrganizationUnitResource
    {
        $this->authorize('create', OrganizationUnit::class);
        $validated = $request->validate((new OrganizationUnitData())->rules());
        $dto = OrganizationUnitData::fromArray($validated);
        $unit = $this->service->execute($dto->toArray());
        return new OrganizationUnitResource($unit);
    }

    public function show(int $id): OrganizationUnitResource
    {
        $unit = $this->service->find($id);
        if (!$unit) {
            abort(404);
        }
        $this->authorize('view', $unit);
        return new OrganizationUnitResource($unit);
    }

    public function update(Request $request, int $id): OrganizationUnitResource
    {
        $unit = $this->service->find($id);
        if (!$unit) {
            abort(404);
        }
        $this->authorize('update', $unit);
        $validated = $request->validate((new OrganizationUnitData())->rules());
        $validated['id'] = $id;
        $dto = OrganizationUnitData::fromArray($validated);
        $updated = $this->updateService->execute($dto->toArray());
        return new OrganizationUnitResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $unit = $this->service->find($id);
        if (!$unit) {
            abort(404);
        }
        $this->authorize('delete', $unit);
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Organization unit deleted successfully']);
    }

    public function tree(Request $request): OrganizationUnitTreeResource
    {
        $this->authorize('viewAny', OrganizationUnit::class);
        $tenantId = (int) tenant_id();
        $rootId = $request->input('root_id');
        $tree = $this->orgUnitRepository->getTree($tenantId, $rootId);
        return new OrganizationUnitTreeResource($tree);
    }

    public function move(MoveOrganizationUnitRequest $request, int $id): JsonResponse
    {
        $unit = $this->service->find($id);
        if (!$unit) {
            abort(404);
        }
        $this->authorize('move', $unit);
        $validated = $request->validated();
        $validated['id'] = $id;
        $dto = MoveOrganizationUnitData::fromArray($validated);
        $this->moveService->execute($dto->toArray());
        return response()->json(['message' => 'Organization unit moved successfully']);
    }

    protected function getModelClass(): string
    {
        return OrganizationUnit::class;
    }
}

