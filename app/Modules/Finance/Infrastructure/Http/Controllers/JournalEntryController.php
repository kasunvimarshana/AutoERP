<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\DeleteJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Finance\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Finance\Domain\Entities\JournalEntry;
use Modules\Finance\Infrastructure\Http\Requests\ListJournalEntryRequest;
use Modules\Finance\Infrastructure\Http\Requests\PostJournalEntryRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreJournalEntryRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateJournalEntryRequest;
use Modules\Finance\Infrastructure\Http\Resources\JournalEntryCollection;
use Modules\Finance\Infrastructure\Http\Resources\JournalEntryResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JournalEntryController extends AuthorizedController
{
    public function __construct(
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
        private readonly UpdateJournalEntryServiceInterface $updateJournalEntryService,
        private readonly DeleteJournalEntryServiceInterface $deleteJournalEntryService,
        private readonly FindJournalEntryServiceInterface $findJournalEntryService,
        private readonly PostJournalEntryServiceInterface $postJournalEntryService,
    ) {}

    public function index(ListJournalEntryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', JournalEntry::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'fiscal_period_id' => $validated['fiscal_period_id'] ?? null,
            'entry_type' => $validated['entry_type'] ?? null,
            'status' => $validated['status'] ?? null,
            'entry_number' => $validated['entry_number'] ?? null,
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $journalEntries = $this->findJournalEntryService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new JournalEntryCollection($journalEntries))->response();
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $this->authorize('create', JournalEntry::class);

        $journalEntry = $this->createJournalEntryService->execute($request->validated());

        return (new JournalEntryResource($journalEntry))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $journalEntry): JournalEntryResource
    {
        $foundJournalEntry = $this->findJournalEntryOrFail($journalEntry);
        $this->authorize('view', $foundJournalEntry);

        return new JournalEntryResource($foundJournalEntry);
    }

    public function update(UpdateJournalEntryRequest $request, int $journalEntry): JournalEntryResource
    {
        $foundJournalEntry = $this->findJournalEntryOrFail($journalEntry);
        $this->authorize('update', $foundJournalEntry);

        $payload = $request->validated();
        $payload['id'] = $journalEntry;

        return new JournalEntryResource($this->updateJournalEntryService->execute($payload));
    }

    public function destroy(int $journalEntry): JsonResponse
    {
        $foundJournalEntry = $this->findJournalEntryOrFail($journalEntry);
        $this->authorize('delete', $foundJournalEntry);

        $this->deleteJournalEntryService->execute(['id' => $journalEntry]);

        return Response::json(['message' => 'Journal entry deleted successfully']);
    }

    public function post(PostJournalEntryRequest $request, int $journalEntry): JournalEntryResource
    {
        $foundJournalEntry = $this->findJournalEntryOrFail($journalEntry);
        $this->authorize('update', $foundJournalEntry);

        $payload = $request->validated();
        $payload['id'] = $journalEntry;

        return new JournalEntryResource($this->postJournalEntryService->execute($payload));
    }

    private function findJournalEntryOrFail(int $journalEntryId): JournalEntry
    {
        $journalEntry = $this->findJournalEntryService->find($journalEntryId);

        if (! $journalEntry) {
            throw new NotFoundHttpException('Journal entry not found.');
        }

        return $journalEntry;
    }
}
