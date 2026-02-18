<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Requests\StoreJournalEntryLineRequest;
use Modules\Accounting\Http\Requests\UpdateJournalEntryLineRequest;
use Modules\Accounting\Http\Resources\JournalEntryLineResource;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;

class JournalEntryLineController extends Controller
{
    public function __construct(
        protected JournalEntryLineRepositoryInterface $lineRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $lines = $this->lineRepository->paginate($perPage);

        return JournalEntryLineResource::collection($lines)->response();
    }

    public function store(StoreJournalEntryLineRequest $request): JsonResponse
    {
        $line = $this->lineRepository->create($request->validated());

        return (new JournalEntryLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $line = $this->lineRepository->findById($id);
        if (! $line) {
            return response()->json(['message' => 'Line not found'], 404);
        }

        return (new JournalEntryLineResource($line))->response();
    }

    public function update(UpdateJournalEntryLineRequest $request, int $id): JsonResponse
    {
        $line = $this->lineRepository->update($id, $request->validated());

        return (new JournalEntryLineResource($line))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->lineRepository->delete($id);

        return response()->json(null, 204);
    }

    public function byEntry(int $entryId): JsonResponse
    {
        $lines = $this->lineRepository->getByJournalEntry($entryId);

        return JournalEntryLineResource::collection($lines)->response();
    }
}
