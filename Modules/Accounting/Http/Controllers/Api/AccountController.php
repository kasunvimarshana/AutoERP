<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Requests\StoreAccountRequest;
use Modules\Accounting\Http\Requests\UpdateAccountRequest;
use Modules\Accounting\Http\Resources\AccountResource;
use Modules\Accounting\Services\AccountService;

/**
 * Account API Controller
 *
 * Handles API requests for chart of accounts management.
 */
class AccountController extends Controller
{
    /**
     * AccountController constructor.
     */
    public function __construct(
        protected AccountService $accountService
    ) {}

    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $accounts = $this->accountService->getPaginated($perPage);

        return AccountResource::collection($accounts)->response();
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->accountService->create($request->validated());

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified account.
     */
    public function show(int $id): JsonResponse
    {
        $account = $this->accountService->findById($id);

        if (! $account) {
            return response()->json([
                'message' => 'Account not found',
            ], 404);
        }

        return (new AccountResource($account))->response();
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        try {
            $account = $this->accountService->update($id, $request->validated());

            return (new AccountResource($account))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified account.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->accountService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get chart of accounts in hierarchical structure.
     */
    public function chartOfAccounts(): JsonResponse
    {
        $accounts = $this->accountService->getChartOfAccounts();

        return AccountResource::collection($accounts)->response();
    }

    /**
     * Get accounts by type.
     */
    public function byType(string $type): JsonResponse
    {
        $accounts = $this->accountService->getByType($type);

        return AccountResource::collection($accounts)->response();
    }

    /**
     * Search accounts.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $accounts = $this->accountService->search($query);

        return AccountResource::collection($accounts)->response();
    }
}
