<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Application\Contracts\TransactionServiceInterface;
use Modules\Transaction\Infrastructure\Http\Resources\TransactionResource;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionServiceInterface $transactionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transactions = $this->transactionService->getAllTransactions($tenantId);

        return response()->json(
            TransactionResource::collection(collect($transactions))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->createTransaction($tenantId, $request->all());

        return response()->json(new TransactionResource($transaction), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->getTransaction($tenantId, $id);

        return response()->json(new TransactionResource($transaction));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->getTransaction($tenantId, $id);

        return response()->json(new TransactionResource($transaction));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Transactions cannot be deleted.'], 405);
    }

    public function post(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->postTransaction($tenantId, $id);

        return response()->json(new TransactionResource($transaction));
    }

    public function void(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $transaction = $this->transactionService->voidTransaction($tenantId, $id);

        return response()->json(new TransactionResource($transaction));
    }

    public function byDateRange(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $from = new \DateTimeImmutable((string) $request->query('from', 'today'));
        $to   = new \DateTimeImmutable((string) $request->query('to', 'today'));

        $transactions = $this->transactionService->getTransactionsByDateRange($tenantId, $from, $to);

        return response()->json(
            TransactionResource::collection(collect($transactions))
        );
    }
}
