<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\UoM\Application\Contracts\CreateUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\DeleteUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\FindUomCategoryServiceInterface;
use Modules\UoM\Application\Contracts\UpdateUomCategoryServiceInterface;
use Modules\UoM\Application\DTOs\UomCategoryData;
use Modules\UoM\Application\DTOs\UpdateUomCategoryData;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Infrastructure\Http\Requests\StoreUomCategoryRequest;
use Modules\UoM\Infrastructure\Http\Requests\UpdateUomCategoryRequest;
use Modules\UoM\Infrastructure\Http\Resources\UomCategoryCollection;
use Modules\UoM\Infrastructure\Http\Resources\UomCategoryResource;

class UomCategoryController extends AuthorizedController
{
    public function __construct(
        protected FindUomCategoryServiceInterface $findService,
        protected CreateUomCategoryServiceInterface $createService,
        protected UpdateUomCategoryServiceInterface $updateService,
        protected DeleteUomCategoryServiceInterface $deleteService,
    ) {}

    public function index(Request $request): UomCategoryCollection
    {
        $this->authorize('viewAny', UomCategory::class);
        $filters = $request->only(['name', 'code', 'is_active', 'tenant_id']);
        $perPage = $request->integer('per_page', 15);
        $page    = $request->integer('page', 1);
        $sort    = $request->input('sort');
        $include = $request->input('include');

        $categories = $this->findService->list($filters, $perPage, $page, $sort, $include);

        return new UomCategoryCollection($categories);
    }

    public function store(StoreUomCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', UomCategory::class);
        $validated = $request->validated();

        $dto = UomCategoryData::fromArray([
            'tenantId'    => $validated['tenant_id'],
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'] ?? null,
            'isActive'    => $validated['is_active'] ?? true,
        ]);

        $category = $this->createService->execute($dto->toArray());

        return (new UomCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(int $id): UomCategoryResource
    {
        $category = $this->findService->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('view', $category);

        return new UomCategoryResource($category);
    }

    public function update(UpdateUomCategoryRequest $request, int $id): UomCategoryResource
    {
        $category = $this->findService->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('update', $category);

        $validated = $request->validated();
        $payload   = ['id' => $id];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('code', $validated)) {
            $payload['code'] = $validated['code'];
        }
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }
        if (array_key_exists('is_active', $validated)) {
            $payload['isActive'] = $validated['is_active'];
        }

        $dto     = UpdateUomCategoryData::fromArray($payload);
        $updated = $this->updateService->execute($dto->toArray() + ['id' => $id]);

        return new UomCategoryResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->findService->find($id);
        if (! $category) {
            abort(404);
        }
        $this->authorize('delete', $category);
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'UoM category deleted successfully']);
    }
}
