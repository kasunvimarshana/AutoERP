<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Financial\Application\Contracts\JournalEntryServiceInterface;
use Modules\Financial\Application\DTOs\JournalEntryData;
use Modules\Financial\Infrastructure\Http\Resources\JournalEntryResource;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;

class JournalEntryController extends BaseController
{
    public function __construct(JournalEntryServiceInterface $service)
    {
        parent::__construct($service, JournalEntryResource::class, JournalEntryData::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return JournalEntryModel::class;
    }

    /**
     * List journal entries with optional filters.
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['status', 'type']));
        $paginator = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->integer('page', 1),
            $request->input('sort'),
            $request->input('include'),
        );

        return JournalEntryResource::collection($paginator);
    }

    /**
     * Create a new journal entry (with lines).
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \Modules\Financial\Application\Contracts\JournalEntryServiceInterface $service */
        $service = $this->service;
        $entry = $service->createJournalEntry($request->all());

        return (new JournalEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single journal entry (with lines).
     */
    public function show(string $id): JsonResponse
    {
        $entry = $this->service->find($id);

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * Update a draft journal entry.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $entry = $this->service->update($id, $request->all());

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * Delete a journal entry (soft delete — only drafts should be deletable).
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * Post a draft journal entry.
     */
    public function post(string $id): JsonResponse
    {
        /** @var \Modules\Financial\Application\Contracts\JournalEntryServiceInterface $service */
        $service = $this->service;
        $entry = $service->postJournalEntry($id);

        return (new JournalEntryResource($entry))->response();
    }

    /**
     * Void a posted journal entry.
     */
    public function void(Request $request, string $id): JsonResponse
    {
        /** @var \Modules\Financial\Application\Contracts\JournalEntryServiceInterface $service */
        $service = $this->service;
        $entry = $service->voidJournalEntry($id, (string) $request->input('void_reason', ''));

        return (new JournalEntryResource($entry))->response();
    }
}
