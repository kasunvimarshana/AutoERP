<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Finance\DTOs\CreateAccountDTO;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * Account API Controller
 *
 * Handles HTTP requests for chart of accounts management.
 */
class AccountController extends BaseController
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $accounts = $this->accountService->getAllAccounts($perPage);

        return $this->successResponse($accounts, 'Accounts retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => ['required', new Enum(AccountType::class)],
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $dto = CreateAccountDTO::fromArray($validated);
        $account = $this->accountService->createAccount($dto);

        return $this->createdResponse($account, 'Account created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->accountService->getAccountById($id);

        return $this->successResponse($account, 'Account retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => ['required', new Enum(AccountType::class)],
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $dto = CreateAccountDTO::fromArray($validated);
        $account = $this->accountService->updateAccount($id, $dto);

        return $this->successResponse($account, 'Account updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->accountService->deleteAccount($id);

        return $this->successResponse(null, 'Account deleted successfully');
    }

    public function byType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', new Enum(AccountType::class)],
        ]);

        $type = AccountType::from($validated['type']);
        $accounts = $this->accountService->getAccountsByType($type);

        return $this->successResponse($accounts, 'Accounts retrieved successfully');
    }

    public function rootAccounts(): JsonResponse
    {
        $accounts = $this->accountService->getRootAccounts();

        return $this->successResponse($accounts, 'Root accounts retrieved successfully');
    }

    public function activeAccounts(): JsonResponse
    {
        $accounts = $this->accountService->getActiveAccounts();

        return $this->successResponse($accounts, 'Active accounts retrieved successfully');
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $accounts = $this->accountService->searchAccounts($validated['search']);

        return $this->successResponse($accounts, 'Search results retrieved successfully');
    }

    public function balance(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'end_date' => 'nullable|date',
        ]);

        $balance = $this->accountService->getAccountBalance($id, $validated['end_date'] ?? null);

        return $this->successResponse(
            ['balance' => $balance],
            'Account balance retrieved successfully'
        );
    }
}
