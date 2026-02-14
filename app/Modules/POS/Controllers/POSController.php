<?php

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\POS\Services\POSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POS Controller
 *
 * @OA\Tag(name="POS", description="Point of Sale endpoints")
 */
class POSController extends Controller
{
    protected POSService $posService;

    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    public function transactions(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $transactions = $this->posService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showTransaction(int $id): JsonResponse
    {
        try {
            $transaction = $this->posService->find($id);

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,mobile_money,bank_transfer',
            'payment_reference' => 'nullable|string|max:100',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $transaction = $this->posService->processCheckout(
                $validated['items'],
                $validated['payment_method'],
                $validated['customer_id'] ?? null,
                [
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'discount' => $validated['discount'] ?? 0,
                    'tax' => $validated['tax'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction completed successfully',
                'data' => $transaction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function voidTransaction(int $id): JsonResponse
    {
        try {
            $result = $this->posService->voidTransaction($id);

            return response()->json([
                'success' => true,
                'message' => 'Transaction voided successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to void transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
