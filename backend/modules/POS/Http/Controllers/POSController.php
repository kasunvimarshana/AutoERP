<?php

declare(strict_types=1);

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Controllers\BaseController;
use Modules\POS\Services\POSCheckoutService;
use Modules\POS\Services\ReceiptService;
use Modules\POS\Services\ReturnRefundService;
use Modules\POS\Services\SaleSuspensionService;
use Modules\POS\Repositories\TransactionRepository;

/**
 * POS Controller
 * 
 * Handles POS-specific operations including checkout, receipts,
 * returns, and suspended sales.
 */
class POSController extends BaseController
{
    public function __construct(
        private POSCheckoutService $checkoutService,
        private ReceiptService $receiptService,
        private ReturnRefundService $returnService,
        private SaleSuspensionService $suspensionService,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Process quick checkout
     *
     * @OA\Post(
     *     path="/api/pos/checkout",
     *     summary="Process POS checkout",
     *     tags={"POS"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cash_register_id", "location_id", "lines", "payments"},
     *             @OA\Property(property="cash_register_id", type="string", format="uuid"),
     *             @OA\Property(property="location_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="lines", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="payments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response="200", description="Checkout successful")
     * )
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cash_register_id' => 'required|uuid|exists:pos_cash_registers,id',
                'location_id' => 'required|uuid|exists:pos_business_locations,id',
                'customer_id' => 'nullable|uuid',
                'lines' => 'required|array|min:1',
                'lines.*.product_id' => 'required|uuid',
                'lines.*.variation_id' => 'nullable|uuid',
                'lines.*.quantity' => 'required|numeric|min:0.01',
                'lines.*.unit_price' => 'required|numeric|min:0',
                'lines.*.discount_amount' => 'nullable|numeric|min:0',
                'lines.*.discount_type' => 'nullable|string|in:fixed,percentage',
                'lines.*.tax_rate' => 'nullable|numeric|min:0',
                'payments' => 'required|array|min:1',
                'payments.*.payment_method_id' => 'required|string',
                'payments.*.amount' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_type' => 'nullable|string|in:fixed,percentage',
            ]);

            $result = $this->checkoutService->processCheckout($validated);

            return $this->success($result, 'Checkout completed successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Process quick sale (simplified checkout)
     *
     * @OA\Post(
     *     path="/api/pos/quick-sale",
     *     summary="Process quick POS sale",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Sale completed")
     * )
     */
    public function quickSale(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cash_register_id' => 'required|uuid',
                'location_id' => 'required|uuid',
                'customer_id' => 'nullable|uuid',
                'lines' => 'required|array|min:1',
                'payment_method_id' => 'nullable|string',
                'total_amount' => 'required|numeric|min:0',
            ]);

            $transaction = $this->checkoutService->quickSale($validated);

            return $this->success($transaction, 'Quick sale completed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Generate and retrieve receipt
     *
     * @OA\Get(
     *     path="/api/pos/transactions/{id}/receipt",
     *     summary="Get transaction receipt",
     *     tags={"POS"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="format", in="query", @OA\Schema(type="string", enum={"thermal","standard","a4"})),
     *     @OA\Response(response="200", description="Receipt generated")
     * )
     */
    public function getReceipt(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findById($id);

            if (!$transaction) {
                return $this->error('Transaction not found', 404);
            }

            $format = $request->input('format', 'thermal');
            $receipt = $this->receiptService->generateReceipt($transaction, $format);

            return $this->success($receipt, 'Receipt generated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Print receipt
     *
     * @OA\Post(
     *     path="/api/pos/transactions/{id}/print",
     *     summary="Print transaction receipt",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Receipt printed")
     * )
     */
    public function printReceipt(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findById($id);

            if (!$transaction) {
                return $this->error('Transaction not found', 404);
            }

            $printerName = $request->input('printer', 'default');
            $success = $this->receiptService->printReceipt($transaction, $printerName);

            if ($success) {
                return $this->success(null, 'Receipt printed successfully');
            }

            return $this->error('Failed to print receipt', 500);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Email receipt
     *
     * @OA\Post(
     *     path="/api/pos/transactions/{id}/email-receipt",
     *     summary="Email transaction receipt",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Receipt emailed")
     * )
     */
    public function emailReceipt(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $transaction = $this->transactionRepository->findById($id);

            if (!$transaction) {
                return $this->error('Transaction not found', 404);
            }

            $success = $this->receiptService->emailReceipt($transaction, $validated['email']);

            if ($success) {
                return $this->success(null, 'Receipt emailed successfully');
            }

            return $this->error('Failed to email receipt', 500);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Process sales return
     *
     * @OA\Post(
     *     path="/api/pos/transactions/{id}/return",
     *     summary="Process sales return",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Return processed")
     * )
     */
    public function processReturn(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lines' => 'required|array|min:1',
                'lines.*.product_id' => 'required|uuid',
                'lines.*.variation_id' => 'nullable|uuid',
                'lines.*.quantity' => 'required|numeric|min:0.01',
                'lines.*.unit_price' => 'required|numeric|min:0',
                'reason' => 'nullable|string',
                'notes' => 'nullable|string',
                'payment_method_id' => 'nullable|string',
            ]);

            $result = $this->returnService->processReturn($id, $validated);

            return $this->success($result, 'Return processed successfully');
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Process full return
     *
     * @OA\Post(
     *     path="/api/pos/transactions/{id}/full-return",
     *     summary="Process full sales return",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Full return processed")
     * )
     */
    public function processFullReturn(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string',
                'notes' => 'nullable|string',
                'payment_method_id' => 'nullable|string',
            ]);

            $result = $this->returnService->processFullReturn($id, $validated);

            return $this->success($result, 'Full return processed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Suspend/pause a sale
     *
     * @OA\Post(
     *     path="/api/pos/sales/suspend",
     *     summary="Suspend a sale transaction",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Sale suspended")
     * )
     */
    public function suspendSale(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'location_id' => 'required|uuid',
                'cash_register_id' => 'required|uuid',
                'customer_id' => 'nullable|uuid',
                'lines' => 'required|array|min:1',
                'subtotal' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'suspension_reason' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            $transaction = $this->suspensionService->suspendSale($validated);

            return $this->success($transaction, 'Sale suspended successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Resume a suspended sale
     *
     * @OA\Post(
     *     path="/api/pos/sales/{id}/resume",
     *     summary="Resume a suspended sale",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Sale resumed")
     * )
     */
    public function resumeSale(string $id): JsonResponse
    {
        try {
            $transaction = $this->suspensionService->resumeSale($id);

            return $this->success($transaction, 'Sale resumed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get suspended sales
     *
     * @OA\Get(
     *     path="/api/pos/sales/suspended",
     *     summary="List suspended sales",
     *     tags={"POS"},
     *     @OA\Parameter(name="cash_register_id", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="location_id", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="List of suspended sales")
     * )
     */
    public function getSuspendedSales(Request $request): JsonResponse
    {
        try {
            if ($request->has('cash_register_id')) {
                $sales = $this->suspensionService->getSuspendedSales($request->input('cash_register_id'));
            } elseif ($request->has('location_id')) {
                $sales = $this->suspensionService->getSuspendedSalesByLocation($request->input('location_id'));
            } else {
                return $this->error('Either cash_register_id or location_id is required', 400);
            }

            return $this->success($sales);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Complete suspended sale
     *
     * @OA\Post(
     *     path="/api/pos/sales/{id}/complete",
     *     summary="Complete a suspended sale",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Sale completed")
     * )
     */
    public function completeSuspendedSale(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lines' => 'nullable|array',
                'payments' => 'required|array|min:1',
                'payments.*.payment_method_id' => 'required|string',
                'payments.*.amount' => 'required|numeric|min:0',
            ]);

            $result = $this->suspensionService->completeSuspendedSale($id, $validated);

            return $this->success($result, 'Suspended sale completed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Cancel suspended sale
     *
     * @OA\Delete(
     *     path="/api/pos/sales/{id}/cancel",
     *     summary="Cancel a suspended sale",
     *     tags={"POS"},
     *     @OA\Response(response="200", description="Sale cancelled")
     * )
     */
    public function cancelSuspendedSale(Request $request, string $id): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $success = $this->suspensionService->cancelSuspendedSale($id, $reason);

            if ($success) {
                return $this->success(null, 'Suspended sale cancelled successfully');
            }

            return $this->error('Failed to cancel suspended sale', 500);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
