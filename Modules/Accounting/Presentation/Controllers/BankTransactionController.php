<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\RecordBankTransactionUseCase;
use Modules\Accounting\Application\UseCases\ReconcileBankTransactionUseCase;
use Modules\Accounting\Infrastructure\Repositories\BankTransactionRepository;
use Modules\Accounting\Presentation\Requests\StoreBankTransactionRequest;
use Modules\Accounting\Presentation\Requests\ReconcileBankTransactionRequest;
use Modules\Shared\Application\ResponseFormatter;

class BankTransactionController extends Controller
{
    public function __construct(
        private RecordBankTransactionUseCase     $recordUseCase,
        private ReconcileBankTransactionUseCase  $reconcileUseCase,
        private BankTransactionRepository        $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreBankTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->recordUseCase->execute($request->validated());
            return ResponseFormatter::success($transaction, 'Bank transaction recorded.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $transaction = $this->repo->findById($id);
        if (! $transaction) {
            return ResponseFormatter::error('Bank transaction not found.', [], 404);
        }
        return ResponseFormatter::success($transaction);
    }

    public function reconcile(ReconcileBankTransactionRequest $request, string $id): JsonResponse
    {
        try {
            $transaction = $this->reconcileUseCase->execute(array_merge(
                $request->validated(),
                ['transaction_id' => $id],
            ));
            return ResponseFormatter::success($transaction, 'Bank transaction reconciled.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
