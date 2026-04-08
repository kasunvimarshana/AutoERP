<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Transaction\Application\Contracts\CreateTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\DeleteTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\FindTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\PostTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\UpdateTransactionServiceInterface;
use Modules\Transaction\Application\Contracts\VoidTransactionServiceInterface;
use Modules\Transaction\Application\DTOs\TransactionData;
use Modules\Transaction\Application\DTOs\UpdateTransactionData;
use Modules\Transaction\Infrastructure\Http\Requests\StoreTransactionRequest;
use Modules\Transaction\Infrastructure\Http\Requests\UpdateTransactionRequest;
use Modules\Transaction\Infrastructure\Http\Resources\TransactionCollection;
use Modules\Transaction\Infrastructure\Http\Resources\TransactionResource;

class TransactionController extends AuthorizedController
{
    public function __construct(
        protected FindTransactionServiceInterface $findService,
        protected CreateTransactionServiceInterface $createService,
        protected UpdateTransactionServiceInterface $updateService,
        protected DeleteTransactionServiceInterface $deleteService,
        protected PostTransactionServiceInterface $postService,
        protected VoidTransactionServiceInterface $voidService,
    ) {}

    public function index(Request $request): TransactionCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'transaction_type']);

        return new TransactionCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = TransactionData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'transactionType' => $v['transaction_type'],
            'amount'          => $v['amount'],
            'transactionDate' => $v['transaction_date'],
            'status'          => $v['status'] ?? 'draft',
            'currencyCode'    => $v['currency_code'] ?? 'USD',
            'exchangeRate'    => $v['exchange_rate'] ?? 1.0,
            'description'     => $v['description'] ?? null,
            'referenceType'   => $v['reference_type'] ?? null,
            'referenceId'     => $v['reference_id'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]);

        $transaction = $this->createService->execute($dto->toArray());

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }

    public function show(int $id): TransactionResource|JsonResponse
    {
        $transaction = $this->findService->find($id);
        if (! $transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new TransactionResource($transaction);
    }

    public function update(UpdateTransactionRequest $request, int $id): TransactionResource
    {
        $v   = $request->validated();
        $dto = UpdateTransactionData::fromArray(array_merge(['id' => $id], [
            'transactionType' => $v['transaction_type'] ?? null,
            'amount'          => $v['amount'] ?? null,
            'transactionDate' => $v['transaction_date'] ?? null,
            'status'          => $v['status'] ?? null,
            'currencyCode'    => $v['currency_code'] ?? null,
            'exchangeRate'    => $v['exchange_rate'] ?? null,
            'description'     => $v['description'] ?? null,
            'referenceType'   => $v['reference_type'] ?? null,
            'referenceId'     => $v['reference_id'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]));

        return new TransactionResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Transaction deleted successfully']);
    }

    public function post(int $id): TransactionResource|JsonResponse
    {
        $transaction = $this->postService->execute(['id' => $id]);

        return new TransactionResource($transaction);
    }

    public function void(Request $request, int $id): TransactionResource|JsonResponse
    {
        $reason      = $request->input('reason', '');
        $transaction = $this->voidService->execute(['id' => $id, 'reason' => $reason]);

        return new TransactionResource($transaction);
    }
}
