<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryTransactionCollection;
use App\Http\Resources\InventoryTransactionResource;
use App\Services\InventoryService;
use App\Repositories\Interfaces\InventoryTransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class InventoryTransactionController extends Controller
{
    public function __construct(
        private readonly InventoryTransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * Return a paginated, filtered list of inventory transactions.
     *
     * Filters: inventory_id, product_id, type, date_from, date_to, sort_by, sort_direction, per_page
     */
    public function index(Request $request): InventoryTransactionCollection|JsonResponse
    {
        try {
            $filters = $request->only([
                'inventory_id',
                'product_id',
                'type',
                'date_from',
                'date_to',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $transactions = $this->transactionRepository->getAll($filters);

            return new InventoryTransactionCollection($transactions);
        } catch (Throwable $e) {
            Log::error('Failed to fetch inventory transactions', [
                'error'   => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve inventory transactions.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single inventory transaction by ID.
     */
    public function show(int $id): InventoryTransactionResource|JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findById($id);

            if ($transaction === null) {
                return response()->json(['message' => 'Transaction not found.'], 404);
            }

            return new InventoryTransactionResource($transaction);
        } catch (Throwable $e) {
            Log::error('Failed to fetch inventory transaction', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve transaction.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
