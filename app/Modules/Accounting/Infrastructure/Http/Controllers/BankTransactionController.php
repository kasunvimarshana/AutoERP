<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\BankTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;
use Modules\Accounting\Infrastructure\Http\Resources\BankTransactionResource;
class BankTransactionController extends Controller
{
    public function __construct(
        private readonly BankTransactionServiceInterface $transactionService,
        private readonly CategorizeTransactionServiceInterface $categorizeService,
        private readonly ImportBankTransactionsServiceInterface $importService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $tenantId      = $request->user()->tenant_id;
        $bankAccountId = $request->query('bank_account_id', '');
        $transactions  = $this->transactionService->getTransactions($tenantId, $bankAccountId, $request->query());
        return response()->json(BankTransactionResource::collection($transactions));
    }
    public function store(Request $request): JsonResponse
    {
        $tenantId    = $request->user()->tenant_id;
        $transaction = $this->transactionService->createTransaction($tenantId, $request->all());
        return response()->json(new BankTransactionResource($transaction), 201);
    }
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId    = $request->user()->tenant_id;
        $transaction = $this->transactionService->getTransaction($tenantId, $id);
        return response()->json(new BankTransactionResource($transaction));
    }
    public function categorize(Request $request, string $id): JsonResponse
    {
        $tenantId    = $request->user()->tenant_id;
        $transaction = $this->categorizeService->categorize(
            $tenantId,
            $id,
            (string) $request->input('account_id'),
        );
        return response()->json(new BankTransactionResource($transaction));
    }
    public function importBatch(Request $request): JsonResponse
    {
        $tenantId      = $request->user()->tenant_id;
        $bankAccountId = (string) $request->input('bank_account_id');
        $transactions  = (array) $request->input('transactions', []);
        $imported      = $this->importService->import($tenantId, $bankAccountId, $transactions);
        return response()->json(BankTransactionResource::collection($imported), 201);
    }
}
