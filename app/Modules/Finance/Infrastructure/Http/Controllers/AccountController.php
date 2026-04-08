<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\AccountServiceInterface;
use Modules\Finance\Application\DTOs\AccountData;
use Modules\Finance\Infrastructure\Http\Requests\StoreAccountRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateAccountRequest;
use Modules\Finance\Infrastructure\Http\Resources\AccountResource;

/**
 * @OA\Tag(name="Finance - Accounts", description="Chart of Accounts management")
 */
final class AccountController extends AuthorizedController
{
    public function __construct(private readonly AccountServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/finance/accounts",
     *     tags={"Finance - Accounts"},
     *     summary="List all accounts for the authenticated tenant",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated account list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['type', 'is_active', 'is_bank_account']));
        $perPage = (int) $request->query('per_page', 15);

        $paginated = $this->service->list($filters, $perPage);

        return AccountResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/finance/accounts",
     *     tags={"Finance - Accounts"},
     *     summary="Create a new account",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreAccountRequest")),
     *     @OA\Response(response=201, description="Account created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = AccountData::fromArray($request->validated());
        $account  = $this->service->create($dto, $tenantId);

        return (new AccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/accounts/{id}",
     *     tags={"Finance - Accounts"},
     *     summary="Get account by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Account details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $account = $this->service->find($id);

        return (new AccountResource($account))->response();
    }

    /**
     * @OA\Put(
     *     path="/api/finance/accounts/{id}",
     *     tags={"Finance - Accounts"},
     *     summary="Update an account",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UpdateAccountRequest")),
     *     @OA\Response(response=200, description="Account updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        $dto     = AccountData::fromArray($request->validated());
        $account = $this->service->update($id, $dto);

        return (new AccountResource($account))->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/finance/accounts/{id}",
     *     tags={"Finance - Accounts"},
     *     summary="Delete an account (soft delete)",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Account deleted"),
     *     @OA\Response(response=422, description="Cannot delete system account")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/accounts/tree",
     *     tags={"Finance - Accounts"},
     *     summary="Retrieve the hierarchical Chart of Accounts tree",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="CoA tree")
     * )
     */
    public function getTree(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $tree     = $this->service->getChartOfAccounts($tenantId);

        return response()->json(['data' => $tree]);
    }
}
