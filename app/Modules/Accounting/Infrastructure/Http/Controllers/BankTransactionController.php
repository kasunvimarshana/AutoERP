<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\BulkReclassifyTransactionsServiceInterface;
use Modules\Accounting\Application\Contracts\CategorizeTransactionServiceInterface;
use Modules\Accounting\Application\Contracts\ImportBankTransactionsServiceInterface;

class BankTransactionController extends Controller
{
    public function __construct(
        private readonly ImportBankTransactionsServiceInterface $importService,
        private readonly CategorizeTransactionServiceInterface $categorizeService,
        private readonly BulkReclassifyTransactionsServiceInterface $reclassifyService,
    ) {}

    public function import(Request $request, int $bankAccountId): JsonResponse
    {
        $count = $this->importService->execute($bankAccountId, $request->input('transactions', []));
        return response()->json(['imported' => $count], 201);
    }

    public function categorize(Request $request, int $id): JsonResponse
    {
        $transaction = $this->categorizeService->execute(
            $id,
            (int)$request->input('expense_category_id'),
            (int)$request->input('account_id'),
        );
        return response()->json($transaction);
    }

    public function autoApplyRules(Request $request): JsonResponse
    {
        $count = $this->categorizeService->autoApplyRules((int)$request->input('tenant_id', 1));
        return response()->json(['categorized' => $count]);
    }

    public function bulkReclassify(Request $request): JsonResponse
    {
        $count = $this->reclassifyService->execute(
            $request->input('transaction_ids', []),
            (int)$request->input('expense_category_id'),
            (int)$request->input('account_id'),
        );
        return response()->json(['reclassified' => $count]);
    }
}
