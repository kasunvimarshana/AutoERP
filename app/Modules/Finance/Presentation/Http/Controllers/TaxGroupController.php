<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\CreateTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\DeleteTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\GetTaxGroupServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\ListTaxGroupsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\TaxGroups\UpdateTaxGroupServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListTaxGroupRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertTaxGroupRequest;
use Modules\Finance\Presentation\Http\Resources\TaxGroupResource;

final class TaxGroupController extends Controller
{
    public function __construct(
        private readonly ListTaxGroupsServiceInterface $listService,
        private readonly GetTaxGroupServiceInterface $getService,
        private readonly CreateTaxGroupServiceInterface $createService,
        private readonly UpdateTaxGroupServiceInterface $updateService,
        private readonly DeleteTaxGroupServiceInterface $deleteService,
    ) {
    }

    public function index(ListTaxGroupRequest $request): JsonResponse
    {
        $criteria = [];
        $validated = $request->validated();

        if (isset($validated['tenant_id'])) {
            $criteria['tenant_id'] = (int) $validated['tenant_id'];
        }

        if (isset($validated['organization_unit_id'])) {
            $criteria['organization_unit_id'] = (int) $validated['organization_unit_id'];
        }

        if (isset($validated['search'])) {
            $search = trim((string) $validated['search']);
            if ($search !== '') {
                $criteria['search'] = $search;
            }
        }

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $criteria['is_active'] = $validated['is_active'];
        }

        $result = $this->listService->execute(
            $criteria,
            (int) ($validated['per_page'] ?? 0),
            (int) ($validated['page'] ?? 0),
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => TaxGroupResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|TaxGroupResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TaxGroupResource($result->valueOrFail());
    }

    public function store(UpsertTaxGroupRequest $request): JsonResponse|TaxGroupResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TaxGroupResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTaxGroupRequest $request, int|string $id): JsonResponse|TaxGroupResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TaxGroupResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
