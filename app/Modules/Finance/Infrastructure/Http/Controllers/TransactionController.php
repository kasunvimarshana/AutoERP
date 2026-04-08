<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\TransactionServiceInterface;
use Modules\Finance\Application\DTOs\TransactionData;
use Modules\Finance\Infrastructure\Http\Requests\StoreTransactionRequest;
use Modules\Finance\Infrastructure\Http\Resources\TransactionResource;

/**
 * @OA\Tag(name="Finance - Transactions", description="Financial transaction management")
 */
final class TransactionController extends AuthorizedController
{
    public function __construct(private readonly TransactionServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/finance/transactions",
     *     tags={"Finance - Transactions"},
     *     summary="List transactions for the authenticated tenant",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated transaction list")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $filters = array_filter($request->only(['type', 'status', 'currency', 'category']));
        $perPage = (int) $request->query('per_page', 15);

        $paginated = $this->service->list($filters, $perPage);

        return TransactionResource::collection($paginated);
    }

    /**
     * @OA\Post(
     *     path="/api/finance/transactions",
     *     tags={"Finance - Transactions"},
     *     summary="Create a new financial transaction",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StoreTransactionRequest")),
     *     @OA\Response(response=201, description="Transaction created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $tenantId    = (int) $request->header('X-Tenant-ID');
        $dto         = TransactionData::fromArray($request->validated());
        $transaction = $this->service->create($dto, $tenantId);

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/finance/transactions/{id}",
     *     tags={"Finance - Transactions"},
     *     summary="Get transaction by ID",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Transaction details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $transaction = $this->service->find($id);

        return (new TransactionResource($transaction))->response();
    }
}
