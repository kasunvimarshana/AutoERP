<?php

namespace App\Http\Controllers;

use App\Services\StockTransactionService;
use Enterprise\Core\Security\AuthorizationContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * InventoryController - Demonstrates RBAC/ABAC in a microservice.
 */
class InventoryController extends Controller
{
    protected StockTransactionService $stockService;
    protected AuthorizationContract $auth;

    public function __construct(StockTransactionService $stockService, AuthorizationContract $auth)
    {
        $this->stockService = $stockService;
        $this->auth = $auth;
    }

    /**
     * Perform a stock adjustment.
     * Protected by the 'inventory.adjust' permission.
     */
    public function adjust(Request $request)
    {
        // 1. RBAC/ABAC Check: User must have the 'inventory.adjust' permission.
        // We can pass context attributes like 'amount' or 'warehouse_id' for ABAC checks.
        $context = [
            'amount' => $request->quantity,
            'warehouse_id' => $request->warehouse_id
        ];

        if (!$this->auth->can('inventory.adjust', $context)) {
            return response()->json(['error' => 'Forbidden: Insufficient permissions for this adjustment.'], 403);
        }

        // 2. Metadata-driven validation (Handled in higher layers, but for demonstration:)
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|string',
            'warehouse_id' => 'required|string',
            'quantity' => 'required|numeric',
            'transaction_type' => 'required|in:ADJUSTMENT,DAMAGE,LOST',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // 3. Perform the adjustment (Ledger-driven, immutable)
            $ledger = $this->stockService->recordMovement(array_merge($request->all(), [
                'tenant_id' => $request->auth_user['tenant_id'], // Inject tenant context from the validated JWT
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment recorded successfully.',
                'data' => $ledger,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
