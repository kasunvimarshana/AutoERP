<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Transaction\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\FindJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Transaction\Application\Contracts\UpdateJournalEntryServiceInterface;
use Modules\Transaction\Application\DTOs\JournalEntryData;
use Modules\Transaction\Application\DTOs\UpdateJournalEntryData;
use Modules\Transaction\Infrastructure\Http\Requests\StoreJournalEntryRequest;
use Modules\Transaction\Infrastructure\Http\Requests\UpdateJournalEntryRequest;
use Modules\Transaction\Infrastructure\Http\Resources\JournalEntryCollection;
use Modules\Transaction\Infrastructure\Http\Resources\JournalEntryResource;

class JournalEntryController extends AuthorizedController
{
    public function __construct(
        protected FindJournalEntryServiceInterface $findService,
        protected CreateJournalEntryServiceInterface $createService,
        protected UpdateJournalEntryServiceInterface $updateService,
        protected PostJournalEntryServiceInterface $postService,
    ) {}

    public function index(Request $request): JournalEntryCollection
    {
        $filters = $request->only(['tenant_id', 'transaction_id', 'account_code', 'status']);

        return new JournalEntryCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = JournalEntryData::fromArray([
            'tenantId'      => $v['tenant_id'],
            'transactionId' => $v['transaction_id'],
            'accountCode'   => $v['account_code'],
            'accountName'   => $v['account_name'],
            'debitAmount'   => $v['debit_amount'] ?? 0.0,
            'creditAmount'  => $v['credit_amount'] ?? 0.0,
            'description'   => $v['description'] ?? null,
            'status'        => $v['status'] ?? 'draft',
            'metadata'      => $v['metadata'] ?? null,
        ]);

        $journalEntry = $this->createService->execute($dto->toArray());

        return (new JournalEntryResource($journalEntry))->response()->setStatusCode(201);
    }

    public function show(int $id): JournalEntryResource|JsonResponse
    {
        $journalEntry = $this->findService->find($id);
        if (! $journalEntry) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new JournalEntryResource($journalEntry);
    }

    public function update(UpdateJournalEntryRequest $request, int $id): JournalEntryResource
    {
        $v   = $request->validated();
        $dto = UpdateJournalEntryData::fromArray(array_merge(['id' => $id], [
            'accountCode'  => $v['account_code'] ?? null,
            'accountName'  => $v['account_name'] ?? null,
            'debitAmount'  => $v['debit_amount'] ?? null,
            'creditAmount' => $v['credit_amount'] ?? null,
            'description'  => $v['description'] ?? null,
            'metadata'     => $v['metadata'] ?? null,
        ]));

        return new JournalEntryResource($this->updateService->execute($dto->toArray()));
    }

    public function post(int $id): JournalEntryResource|JsonResponse
    {
        $journalEntry = $this->postService->execute(['id' => $id]);

        return new JournalEntryResource($journalEntry);
    }
}
