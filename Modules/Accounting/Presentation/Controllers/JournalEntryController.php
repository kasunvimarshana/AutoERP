<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\CreateJournalEntryUseCase;
use Modules\Accounting\Application\UseCases\PostJournalEntryUseCase;
use Modules\Accounting\Infrastructure\Repositories\JournalEntryRepository;
use Modules\Accounting\Presentation\Requests\StoreJournalEntryRequest;
use Modules\Shared\Application\ResponseFormatter;

class JournalEntryController extends Controller
{
    public function __construct(
        private CreateJournalEntryUseCase $createUseCase,
        private PostJournalEntryUseCase   $postUseCase,
        private JournalEntryRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $entry = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($entry, 'Journal entry created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->repo->findById($id);
        if (! $entry) {
            return ResponseFormatter::error('Journal entry not found.', [], 404);
        }
        return ResponseFormatter::success($entry);
    }

    public function post(string $id): JsonResponse
    {
        try {
            $entry = $this->postUseCase->execute(['id' => $id]);
            return ResponseFormatter::success($entry, 'Journal entry posted.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Journal entry deleted.');
    }
}
