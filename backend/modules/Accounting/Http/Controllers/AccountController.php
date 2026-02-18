<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Services\AccountService;
use Modules\Core\Http\Controllers\BaseController;

/**
 * Account Controller
 *
 * Manages chart of accounts with hierarchical structure for double-entry accounting.
 * Supports account types: asset, liability, equity, revenue, and expense.
 */
class AccountController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private AccountService $accountService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/accounting/accounts",
     *     summary="List all accounts",
     *     description="Retrieve paginated list of chart of accounts with filtering by type, status, parent account, and search capabilities",
     *     operationId="accountsIndex",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by account type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asset", "liability", "equity", "revenue", "expense"}, example="asset")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="Filter by parent account ID (for hierarchical accounts)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in account code, name, or description",
     *         required=false,
     *         @OA\Schema(type="string", example="cash")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Account")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/accounting/accounts?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/accounting/accounts?page=5"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/accounting/accounts?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'type' => $request->input('type'),
                'is_active' => $request->boolean('is_active'),
                'parent_id' => $request->input('parent_id'),
                'search' => $request->input('search'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $accounts = $this->accountService->getAll($filters);

            return $this->success($accounts);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch accounts: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/accounting/accounts/tree",
     *     summary="Get account tree structure",
     *     description="Retrieve hierarchical tree structure of all accounts for chart of accounts display",
     *     operationId="accountsTree",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Account tree retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Hierarchical array of accounts with nested children",
     *                 @OA\Items(ref="#/components/schemas/Account")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function tree(): JsonResponse
    {
        try {
            $tree = $this->accountService->getAccountTree();

            return $this->success($tree);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch account tree: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/accounting/accounts",
     *     summary="Create a new account",
     *     description="Create a new account in the chart of accounts with support for hierarchical parent-child relationships",
     *     operationId="accountsStore",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Account data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreAccountRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid or duplicate account code",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|uuid|exists:accounts,id',
                'type' => 'required|string|in:asset,liability,equity,revenue,expense',
                'code' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'currency_code' => 'nullable|string|size:3',
                'is_active' => 'nullable|boolean',
            ]);

            $account = $this->accountService->create($validated);

            return $this->created($account, 'Account created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create account: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/accounting/accounts/{id}",
     *     summary="Get account details",
     *     description="Retrieve detailed information for a specific account including balance and transaction history",
     *     operationId="accountsShow",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Account ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $account = $this->accountService->getById($id);

            return $this->success($account);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Account not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch account: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/accounting/accounts/{id}",
     *     summary="Update an account",
     *     description="Update an existing account. System accounts may have restrictions on updates.",
     *     operationId="accountsUpdate",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Account ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Account data to update",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateAccountRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data or system account restriction",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id' => 'nullable|uuid|exists:accounts,id',
                'type' => 'sometimes|string|in:asset,liability,equity,revenue,expense',
                'code' => 'sometimes|string|max:50',
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'currency_code' => 'nullable|string|size:3',
                'is_active' => 'nullable|boolean',
            ]);

            $account = $this->accountService->update($id, $validated);

            return $this->updated($account, 'Account updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Account not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update account: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/accounting/accounts/{id}",
     *     summary="Delete an account",
     *     description="Delete an account from the chart of accounts. System accounts and accounts with transactions cannot be deleted.",
     *     operationId="accountsDestroy",
     *     tags={"Accounting-ChartOfAccounts"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Account ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Account deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Cannot delete account with transactions or system account",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Account not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->accountService->delete($id);

            return $this->deleted('Account deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Account not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete account: '.$e->getMessage(), 500);
        }
    }
}
