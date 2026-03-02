<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Accounting\Application\Commands\CreateJournalEntryCommand;
use Modules\Accounting\Application\Commands\DeleteJournalEntryCommand;
use Modules\Accounting\Application\Commands\PostJournalEntryCommand;
use Modules\Accounting\Application\Services\JournalEntryService;
use Modules\Accounting\Interfaces\Http\Requests\CreateJournalEntryRequest;
use Modules\Accounting\Interfaces\Http\Requests\PostJournalEntryRequest;
use Modules\Accounting\Interfaces\Http\Resources\JournalEntryResource;

class JournalEntryController extends BaseController
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->journalEntryService->listJournalEntries($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($entry) => (new JournalEntryResource($entry))->resolve(),
                $result['items']
            ),
            message: 'Journal entries retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateJournalEntryRequest $request): JsonResponse
    {
        try {
            $entry = $this->journalEntryService->createJournalEntry(new CreateJournalEntryCommand(
                tenantId: (int) $request->validated('tenant_id'),
                entryDate: $request->validated('entry_date'),
                reference: $request->validated('reference'),
                description: $request->validated('description'),
                currency: $request->validated('currency', config('currency.default', 'LKR')),
                lines: $request->validated('lines'),
            ));

            return $this->success(
                data: (new JournalEntryResource($entry))->resolve(),
                message: 'Journal entry created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $entry = $this->journalEntryService->findJournalEntryById($id, $tenantId);

        if ($entry === null) {
            return $this->error('Journal entry not found', status: 404);
        }

        return $this->success(
            data: (new JournalEntryResource($entry))->resolve(),
            message: 'Journal entry retrieved successfully',
        );
    }

    public function post(PostJournalEntryRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = (int) $request->query('tenant_id', '0');

            $entry = $this->journalEntryService->postJournalEntry(new PostJournalEntryCommand(
                id: $id,
                tenantId: $tenantId,
            ));

            return $this->success(
                data: (new JournalEntryResource($entry))->resolve(),
                message: 'Journal entry posted successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->journalEntryService->deleteJournalEntry(new DeleteJournalEntryCommand($id, $tenantId));

            return $this->success(message: 'Journal entry deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }
}
