<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use Modules\Accounting\Http\Requests\UpdateJournalEntryRequest;
use Modules\Accounting\Http\Resources\JournalEntryResource;
use Modules\Accounting\Services\JournalEntryService;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalEntryService $journalEntryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $entries = $this->journalEntryService->getPaginated($perPage);

        return JournalEntryResource::collection($entries)->response();
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->create($request->validated());

            return (new JournalEntryResource($entry->load('lines.account')))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        $entry = $this->journalEntryService->findById($id);
        if (! $entry) {
            return response()->json(['message' => 'Journal entry not found'], 404);
        }

        return (new JournalEntryResource($entry->load('lines.account')))->response();
    }

    public function update(UpdateJournalEntryRequest $request, int $id): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->update($id, $request->validated());

            return (new JournalEntryResource($entry->load('lines.account')))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->journalEntryService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function post(int $id): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->post($id);

            return (new JournalEntryResource($entry))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reverse(int $id, Request $request): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->reverse($id, $request->all());

            return (new JournalEntryResource($entry))->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function checkBalance(int $id): JsonResponse
    {
        $entry = $this->journalEntryService->findById($id);
        if (! $entry) {
            return response()->json(['message' => 'Journal entry not found'], 404);
        }
        $isBalanced = $this->journalEntryService->validateBalance($entry);

        return response()->json(['is_balanced' => $isBalanced]);
    }
}
