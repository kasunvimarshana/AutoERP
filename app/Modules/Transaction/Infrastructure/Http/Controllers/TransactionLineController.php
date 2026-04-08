<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Application\Contracts\TransactionLineServiceInterface;
use Modules\Transaction\Infrastructure\Http\Resources\TransactionLineResource;

class TransactionLineController extends Controller
{
    public function __construct(
        private readonly TransactionLineServiceInterface $transactionLineService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId      = $request->user()->tenant_id;
        $transactionId = (string) $request->query('transaction_id', '');
        $lines = $this->transactionLineService->getLinesForTransaction($tenantId, $transactionId);

        return response()->json(
            TransactionLineResource::collection(collect($lines))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->transactionLineService->addLine($tenantId, $request->all());

        return response()->json(new TransactionLineResource($line), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->transactionLineService->getLine($tenantId, $id);

        return response()->json(new TransactionLineResource($line));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $line = $this->transactionLineService->updateLine($tenantId, $id, $request->all());

        return response()->json(new TransactionLineResource($line));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->transactionLineService->deleteLine($tenantId, $id);

        return response()->json(null, 204);
    }
}
