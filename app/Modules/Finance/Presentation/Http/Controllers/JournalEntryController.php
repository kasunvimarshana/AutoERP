<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\GetJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\ListJournalEntriesServiceInterface;
use Modules\Finance\Application\Contracts\UseCases\JournalEntries\UpdateJournalEntryServiceInterface;
use Modules\Finance\Presentation\Http\Requests\ListJournalEntryRequest;
use Modules\Finance\Presentation\Http\Requests\UpsertJournalEntryRequest;
use Modules\Finance\Presentation\Http\Resources\JournalEntryResource;

final class JournalEntryController extends Controller
{
    public function __construct(
        private readonly ListJournalEntriesServiceInterface $listService,
        private readonly GetJournalEntryServiceInterface $getService,
        private readonly CreateJournalEntryServiceInterface $createService,
        private readonly UpdateJournalEntryServiceInterface $updateService,
        private readonly DeleteJournalEntryServiceInterface $deleteService,
    ) {
    }

    public function index(ListJournalEntryRequest $request): JsonResponse
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

        if (array_key_exists('fiscal_period_id', $validated) && $validated['fiscal_period_id'] !== null) {
            $criteria['fiscal_period_id'] = $validated['fiscal_period_id'];
        }

        if (array_key_exists('entry_type', $validated) && $validated['entry_type'] !== null) {
            $criteria['entry_type'] = $validated['entry_type'];
        }

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $criteria['status'] = $validated['status'];
        }

        if (array_key_exists('reference_type', $validated) && $validated['reference_type'] !== null) {
            $criteria['reference_type'] = $validated['reference_type'];
        }

        if (array_key_exists('reference_id', $validated) && $validated['reference_id'] !== null) {
            $criteria['reference_id'] = $validated['reference_id'];
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
            'data' => JournalEntryResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|JournalEntryResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new JournalEntryResource($result->valueOrFail());
    }

    public function store(UpsertJournalEntryRequest $request): JsonResponse|JournalEntryResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new JournalEntryResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertJournalEntryRequest $request, int|string $id): JsonResponse|JournalEntryResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'FINANCE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new JournalEntryResource($result->valueOrFail());
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
