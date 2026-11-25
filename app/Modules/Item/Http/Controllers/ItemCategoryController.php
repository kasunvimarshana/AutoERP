<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemCategoryRequest;
use Modules\Item\Http\Requests\UpdateItemCategoryRequest;
use Modules\Item\Http\Resources\ItemCategoryResource;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemCategoryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ItemCategoryController
{
    public function __construct(
        private readonly ItemCategoryService $service,
        private readonly ItemAuthorizationService $authorization,
    ) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(),
        ));
    }

    public function lookup(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(), true,
        ));
    }

    public function store(StoreItemCategoryRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);

        return (new ItemCategoryResource($this->service->create(
            $request->payload(), $request->tenantId(), $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListItemRequest $request, int $item_category): ItemCategoryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return new ItemCategoryResource($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));
    }

    public function update(UpdateItemCategoryRequest $request, int $item_category): ItemCategoryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $item_category, $expectedVersion) {
            $category = $this->service->find($item_category, $request->tenantId(), $request->organizationUnitId());
            $category = $category->newQuery()->lockForUpdate()->findOrFail($category->getKey());

            if ($expectedVersion !== null && (int) $category->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Item category was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $category->row_version) + 1;
            $saved = $this->service->update($category, $request->payload());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new ItemCategoryResource($updated);
    }

    public function destroy(ListItemRequest $request, int $item_category): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);

        $this->service->delete($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }
}
