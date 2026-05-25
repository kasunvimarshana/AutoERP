<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\GetFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\ListFiscalYearsServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\FiscalYears\UpdateFiscalYearServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListFiscalYearRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertFiscalYearRequest;
use Modules\Finance\Presentation\Http\Resources\FiscalYearResource;

final class FiscalYearController extends Controller
{
    public function __construct(
        private readonly ListFiscalYearsServiceInterface $listService,
        private readonly GetFiscalYearServiceInterface $getService,
        private readonly CreateFiscalYearServiceInterface $createService,
        private readonly UpdateFiscalYearServiceInterface $updateService,
        private readonly DeleteFiscalYearServiceInterface $deleteService,
    ) {
    }

    public function index(ListFiscalYearRequest $request): JsonResponse
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

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $criteria['status'] = $validated['status'];
        }

        if (array_key_exists('is_current', $validated) && $validated['is_current'] !== null) {
            $criteria['is_current'] = $validated['is_current'];
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
            'data' => FiscalYearResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|FiscalYearResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new FiscalYearResource($result->valueOrFail());
    }

    public function store(UpsertFiscalYearRequest $request): JsonResponse|FiscalYearResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new FiscalYearResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertFiscalYearRequest $request, int|string $id): JsonResponse|FiscalYearResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new FiscalYearResource($result->valueOrFail());
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
