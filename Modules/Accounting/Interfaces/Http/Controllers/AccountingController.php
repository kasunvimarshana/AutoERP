<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\DTOs\CreateFiscalPeriodDTO;
use Modules\Accounting\Application\DTOs\CreateJournalEntryDTO;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;

/**
 * Accounting controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to AccountingService.
 *
 * @OA\Tag(name="Accounting", description="Double-entry bookkeeping endpoints")
 */
class AccountingController extends Controller
{
    public function __construct(private readonly AccountingService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/journals",
     *     tags={"Accounting"},
     *     summary="Create a new journal entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fiscal_period_id","reference_number","entry_date","lines"},
     *             @OA\Property(property="fiscal_period_id", type="integer"),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="entry_date", type="string", format="date"),
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"account_id","type","amount"},
     *                     @OA\Property(property="account_id", type="integer"),
     *                     @OA\Property(property="type", type="string", enum={"debit","credit"}),
     *                     @OA\Property(property="amount", type="string", example="1000.0000"),
     *                     @OA\Property(property="description", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Journal entry created"),
     *     @OA\Response(response=422, description="Validation error or unbalanced entry")
     * )
     */
    public function createEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_period_id'         => ['required', 'integer'],
            'reference_number'         => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'entry_date'               => ['required', 'date'],
            'lines'                    => ['required', 'array', 'min:2'],
            'lines.*.account_id'       => ['required', 'integer'],
            'lines.*.type'             => ['required', 'string', 'in:debit,credit'],
            'lines.*.amount'           => ['required', 'numeric', 'min:0'],
            'lines.*.description'      => ['nullable', 'string'],
        ]);

        $dto   = CreateJournalEntryDTO::fromArray($validated);
        $entry = $this->service->createJournalEntry($dto);

        return ApiResponse::created($entry->load('lines'), 'Journal entry created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/journals",
     *     tags={"Accounting"},
     *     summary="List journal entries",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of journal entries"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listEntries(Request $request): JsonResponse
    {
        $entries = $this->service->listEntries($request->query());

        return ApiResponse::success($entries, 'Journal entries retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/journals/{id}/post",
     *     tags={"Accounting"},
     *     summary="Post a draft journal entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Journal entry posted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function postEntry(int $id): JsonResponse
    {
        $entry = $this->service->postEntry($id);

        return ApiResponse::success($entry, 'Journal entry posted.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/accounts",
     *     tags={"Accounting"},
     *     summary="List chart-of-accounts entries",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of accounts"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listAccounts(): JsonResponse
    {
        $accounts = $this->service->listAccounts();

        return ApiResponse::success($accounts, 'Accounts retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/accounting/accounts",
     *     tags={"Accounting"},
     *     summary="Create a new chart-of-accounts entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_type_id","code","name"},
     *             @OA\Property(property="account_type_id", type="integer"),
     *             @OA\Property(property="parent_account_id", type="integer", nullable=true),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Account created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type_id'   => ['required', 'integer'],
            'parent_account_id' => ['nullable', 'integer'],
            'code'              => ['required', 'string', 'max:50'],
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $account = $this->service->createAccount($validated);

        return ApiResponse::created($account, 'Account created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/fiscal-periods",
     *     tags={"Accounting"},
     *     summary="List fiscal periods for the tenant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of fiscal periods"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listFiscalPeriods(): JsonResponse
    {
        $periods = $this->service->listFiscalPeriods();

        return ApiResponse::success($periods, 'Fiscal periods retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/accounting/fiscal-periods",
     *     tags={"Accounting"},
     *     summary="Create a new fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","start_date","end_date"},
     *             @OA\Property(property="name", type="string", example="FY2026-Q1"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="is_closed", type="boolean", default=false)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Fiscal period created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createFiscalPeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_closed'  => ['nullable', 'boolean'],
        ]);

        $dto    = CreateFiscalPeriodDTO::fromArray($validated);
        $period = $this->service->createFiscalPeriod($dto);

        return ApiResponse::created($period, 'Fiscal period created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/accounts/{id}",
     *     tags={"Accounting"},
     *     summary="Get a single chart-of-accounts entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Account data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showAccount(int $id): JsonResponse
    {
        $account = $this->service->showAccount($id);

        return ApiResponse::success($account, 'Account retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/accounting/accounts/{id}",
     *     tags={"Accounting"},
     *     summary="Update a chart-of-accounts entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Account updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateAccount(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'account_type_id'   => ['sometimes', 'integer'],
            'parent_account_id' => ['nullable', 'integer'],
            'code'              => ['sometimes', 'required', 'string', 'max:50'],
            'name'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $account = $this->service->updateAccount($id, $validated);

        return ApiResponse::success($account, 'Account updated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/journals/{id}",
     *     tags={"Accounting"},
     *     summary="Get a single journal entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Journal entry data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showJournalEntry(int $id): JsonResponse
    {
        $entry = $this->service->showJournalEntry($id);

        return ApiResponse::success($entry, 'Journal entry retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/fiscal-periods/{id}",
     *     tags={"Accounting"},
     *     summary="Get a single fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fiscal period data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showFiscalPeriod(int $id): JsonResponse
    {
        $period = $this->service->showFiscalPeriod($id);

        return ApiResponse::success($period, 'Fiscal period retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/fiscal-periods/{id}/trial-balance",
     *     tags={"Accounting"},
     *     summary="Generate a trial balance for a fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Trial balance data"),
     *     @OA\Response(response=404, description="Fiscal period not found")
     * )
     */
    public function getTrialBalance(int $id): JsonResponse
    {
        $trialBalance = $this->service->getTrialBalance($id);

        return ApiResponse::success($trialBalance, 'Trial balance generated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/fiscal-periods/{id}/profit-and-loss",
     *     tags={"Accounting"},
     *     summary="Generate a profit and loss statement for a fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Profit and loss data"),
     *     @OA\Response(response=404, description="Fiscal period not found")
     * )
     */
    public function getProfitAndLoss(int $id): JsonResponse
    {
        $pnl = $this->service->getProfitAndLoss($id);

        return ApiResponse::success($pnl, 'Profit and loss statement generated.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounting/fiscal-periods/{id}/balance-sheet",
     *     tags={"Accounting"},
     *     summary="Generate a balance sheet for a fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Balance sheet data"),
     *     @OA\Response(response=404, description="Fiscal period not found")
     * )
     */
    public function getBalanceSheet(int $id): JsonResponse
    {
        $balanceSheet = $this->service->getBalanceSheet($id);

        return ApiResponse::success($balanceSheet, 'Balance sheet generated.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/accounting/fiscal-periods/{id}/close",
     *     tags={"Accounting"},
     *     summary="Close a fiscal period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fiscal period closed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function closeFiscalPeriod(int $id): JsonResponse
    {
        $period = $this->service->closeFiscalPeriod($id);

        return ApiResponse::success($period, 'Fiscal period closed.');
    }
}
