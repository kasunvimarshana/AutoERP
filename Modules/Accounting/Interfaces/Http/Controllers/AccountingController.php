<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Commands\PostJournalEntryCommand;
use Modules\Accounting\Application\Handlers\PostJournalEntryHandler;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Enums\AccountType;

class AccountingController extends Controller
{
    public function __construct(
        private readonly AccountRepositoryInterface      $accountRepo,
        private readonly JournalEntryRepositoryInterface $journalRepo,
        private readonly PostJournalEntryHandler         $postHandler,
    ) {}

    public function accounts(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $accounts = $this->accountRepo->findAll($tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Accounts retrieved.',
            'data'    => array_map(fn ($a) => [
                'id'             => $a->getId(),
                'code'           => $a->getCode(),
                'name'           => $a->getName(),
                'type'           => $a->getType()->value,
                'normal_balance' => $a->getNormalBalance(),
                'is_active'      => $a->isActive(),
            ], $accounts),
            'errors'  => null,
        ]);
    }

    public function createAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'      => 'required|string|max:20',
            'name'      => 'required|string|max:255',
            'type'      => 'required|string|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'is_active' => 'nullable|boolean',
        ]);

        $accountType = AccountType::from($validated['type']);

        $account = new Account(
            id: 0,
            tenantId: (int) $request->attributes->get('tenant_id'),
            code: $validated['code'],
            name: $validated['name'],
            type: $accountType,
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            isActive: $validated['is_active'] ?? true,
            normalBalance: $accountType->normalBalance(),
        );

        $saved = $this->accountRepo->save($account);

        return response()->json([
            'success' => true,
            'message' => 'Account created.',
            'data'    => [
                'id'             => $saved->getId(),
                'code'           => $saved->getCode(),
                'name'           => $saved->getName(),
                'type'           => $saved->getType()->value,
                'normal_balance' => $saved->getNormalBalance(),
                'is_active'      => $saved->isActive(),
            ],
            'errors'  => null,
        ], 201);
    }

    public function journal(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $page     = (int) $request->query('page', 1);
        $perPage  = min((int) $request->query('per_page', 25), 100);

        $result = $this->journalRepo->findPaginated($tenantId, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Journal entries retrieved.',
            'data'    => array_map(fn ($e) => [
                'id'           => $e->getId(),
                'entry_number' => $e->getEntryNumber(),
                'entry_date'   => $e->getEntryDate()->format('Y-m-d'),
                'description'  => $e->getDescription(),
                'is_posted'    => $e->isPosted(),
                'lines'        => array_map(fn ($l) => [
                    'account_id'    => $l->getAccountId(),
                    'description'   => $l->getDescription(),
                    'debit_amount'  => $l->getDebitAmount(),
                    'credit_amount' => $l->getCreditAmount(),
                ], $e->getLines()),
            ], $result['data']),
            'meta'    => [
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'per_page'     => $result['per_page'],
                'total'        => $result['total'],
            ],
            'errors'  => null,
        ]);
    }

    public function postEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date'            => 'required|date',
            'description'           => 'required|string|max:500',
            'lines'                 => 'required|array|min:2',
            'lines.*.account_id'    => 'required|integer|exists:accounts,id',
            'lines.*.debit_amount'  => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
            'lines.*.description'   => 'nullable|string',
            'reference_type'        => 'nullable|string',
            'reference_id'          => 'nullable|integer',
        ]);

        try {
            $entry = $this->postHandler->handle(new PostJournalEntryCommand(
                tenantId:      (int) $request->attributes->get('tenant_id'),
                entryDate:     $validated['entry_date'],
                description:   $validated['description'],
                lines:         $validated['lines'],
                referenceType: $validated['reference_type'] ?? null,
                referenceId:   isset($validated['reference_id']) ? (int) $validated['reference_id'] : null,
                postedBy:      $request->user()?->id,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Journal entry posted.',
                'data'    => ['id' => $entry->getId(), 'entry_number' => $entry->getEntryNumber()],
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['journal' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $accounts = $this->accountRepo->findActiveByTypes(
            array_map(fn (AccountType $t) => $t->value, AccountType::cases()),
            $tenantId
        );

        $totalDebits  = '0.0000';
        $totalCredits = '0.0000';

        $rows = array_map(function (Account $a) use (&$totalDebits, &$totalCredits): array {
            $balance = $a->getCurrentBalance();
            $debit   = '0.0000';
            $credit  = '0.0000';

            if (bccomp($balance, '0', 4) >= 0) {
                if ($a->isDebitNormal()) {
                    $debit = $balance;
                } else {
                    $credit = $balance;
                }
            } else {
                $abs = bcsub('0', $balance, 4);
                if ($a->isDebitNormal()) {
                    $credit = $abs;
                } else {
                    $debit = $abs;
                }
            }

            $totalDebits  = bcadd($totalDebits, $debit, 4);
            $totalCredits = bcadd($totalCredits, $credit, 4);

            return ['code' => $a->getCode(), 'name' => $a->getName(), 'type' => $a->getType()->value, 'debit' => $debit, 'credit' => $credit];
        }, $accounts);

        return response()->json([
            'success' => true,
            'message' => 'Trial balance generated.',
            'data'    => [
                'rows'          => $rows,
                'total_debits'  => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced'   => bccomp($totalDebits, $totalCredits, 4) === 0,
            ],
            'errors'  => null,
        ]);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $from     = $request->query('from');
        $to       = $request->query('to');

        $accounts = $this->accountRepo->findActiveByTypes(
            [AccountType::REVENUE->value, AccountType::EXPENSE->value],
            $tenantId
        );

        $revenues    = '0.0000';
        $expenses    = '0.0000';
        $revenueRows = [];
        $expenseRows = [];

        foreach ($accounts as $a) {
            $balance = $a->getCurrentBalance();
            $row     = ['code' => $a->getCode(), 'name' => $a->getName(), 'amount' => $balance];

            if ($a->getType() === AccountType::REVENUE) {
                $revenues      = bcadd($revenues, $balance, 4);
                $revenueRows[] = $row;
            } else {
                $expenses      = bcadd($expenses, $balance, 4);
                $expenseRows[] = $row;
            }
        }

        $netProfit = bcsub($revenues, $expenses, 4);

        return response()->json([
            'success' => true,
            'message' => 'Profit and loss report generated.',
            'data'    => [
                'period'        => ['from' => $from, 'to' => $to],
                'revenues'      => $revenueRows,
                'expenses'      => $expenseRows,
                'total_revenue' => $revenues,
                'total_expense' => $expenses,
                'net_profit'    => $netProfit,
            ],
            'errors'  => null,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $asAt     = $request->query('as_at', now()->toDateString());

        $accounts = $this->accountRepo->findActiveByTypes(
            [AccountType::ASSET->value, AccountType::LIABILITY->value, AccountType::EQUITY->value],
            $tenantId
        );

        $totalAssets      = '0.0000';
        $totalLiabilities = '0.0000';
        $totalEquity      = '0.0000';
        $assetRows        = [];
        $liabilityRows    = [];
        $equityRows       = [];

        foreach ($accounts as $a) {
            $balance = $a->getCurrentBalance();
            $row     = ['code' => $a->getCode(), 'name' => $a->getName(), 'amount' => $balance];

            if ($a->getType() === AccountType::ASSET) {
                $totalAssets = bcadd($totalAssets, $balance, 4);
                $assetRows[] = $row;
            } elseif ($a->getType() === AccountType::LIABILITY) {
                $totalLiabilities = bcadd($totalLiabilities, $balance, 4);
                $liabilityRows[]  = $row;
            } elseif ($a->getType() === AccountType::EQUITY) {
                $totalEquity  = bcadd($totalEquity, $balance, 4);
                $equityRows[] = $row;
            }
        }

        $totalLiabilityAndEquity = bcadd($totalLiabilities, $totalEquity, 4);

        return response()->json([
            'success' => true,
            'message' => 'Balance sheet generated.',
            'data'    => [
                'as_at'                    => $asAt,
                'assets'                   => $assetRows,
                'liabilities'              => $liabilityRows,
                'equity'                   => $equityRows,
                'total_assets'             => $totalAssets,
                'total_liabilities'        => $totalLiabilities,
                'total_equity'             => $totalEquity,
                'total_liabilities_equity' => $totalLiabilityAndEquity,
                'is_balanced'              => bccomp($totalAssets, $totalLiabilityAndEquity, 4) === 0,
            ],
            'errors'  => null,
        ]);
    }
}

