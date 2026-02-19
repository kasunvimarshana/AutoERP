<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreAccountRequest;
use Modules\Accounting\Http\Requests\UpdateAccountRequest;
use Modules\Accounting\Http\Resources\AccountResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Repositories\AccountRepository;
use Modules\Accounting\Services\AccountingService;
use Modules\Core\Http\Responses\ApiResponse;

class AccountController extends Controller
{
    public function __construct(
        private AccountRepository $accountRepository,
        private AccountingService $accountingService
    ) {}

    /**
     * Display a listing of accounts
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Account::class);

        $filters = $request->only([
            'type',
            'status',
            'organization_id',
            'parent_id',
            'search'
        ]);

        if ($request->has('is_bank_account')) {
            $filters['is_bank_account'] = $request->boolean('is_bank_account');
        }

        if ($request->has('is_reconcilable')) {
            $filters['is_reconcilable'] = $request->boolean('is_reconcilable');
        }

        $perPage = $request->get('per_page', 15);
        $accounts = $this->accountRepository->findWithFilters($filters, $perPage);

        return ApiResponse::paginated(
            $accounts->setCollection(
                $accounts->getCollection()->map(fn ($account) => new AccountResource($account))
            ),
            'Accounts retrieved successfully'
        );
    }

    /**
     * Store a newly created account
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorize('create', Account::class);

        $data = $request->validated();

        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['organization_id'] = $data['organization_id'] ?? $request->user()->currentOrganization()->id;

        $account = $this->accountingService->createAccount($data);

        return ApiResponse::success(
            new AccountResource($account->load('parent')),
            'Account created successfully',
            201
        );
    }

    /**
     * Display the specified account
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $account->load(['parent', 'children', 'organization']);

        return ApiResponse::success(
            new AccountResource($account),
            'Account retrieved successfully'
        );
    }

    /**
     * Update the specified account
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $data = $request->validated();

        $account = $this->accountingService->updateAccount($account->id, $data);

        return ApiResponse::success(
            new AccountResource($account->load('parent')),
            'Account updated successfully'
        );
    }

    /**
     * Remove the specified account
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $this->accountingService->deleteAccount($account->id);

        return ApiResponse::success(
            null,
            'Account deleted successfully'
        );
    }

    /**
     * Get account balance
     */
    public function balance(Request $request, Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $asOfDate = $request->get('as_of_date');
        $balance = $this->accountingService->getAccountBalance($account->id, $asOfDate);

        return ApiResponse::success([
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'balance' => $balance,
            'as_of_date' => $asOfDate ?? now()->toDateString(),
        ], 'Account balance retrieved successfully');
    }
}
