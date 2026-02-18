<?php

declare(strict_types=1);

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\POS\Services\TransactionService;
use Modules\POS\Repositories\TransactionRepository;
use Modules\POS\Models\Transaction;

class TransactionController extends BaseController
{
    public function __construct(
        private TransactionService $transactionService,
        private TransactionRepository $transactionRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['location_id', 'type', 'status', 'payment_status', 'from_date', 'to_date']);
        $perPage = $request->input('per_page', 15);

        $transactions = $this->transactionRepository->all($filters, $perPage);

        return $this->success($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|uuid|exists:pos_business_locations,id',
            'type' => 'required|string',
            'contact_id' => 'nullable|uuid',
            'cash_register_id' => 'nullable|uuid|exists:pos_cash_registers,id',
            'transaction_date' => 'nullable|date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string',
            'shipping_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|uuid',
            'lines.*.variation_id' => 'nullable|uuid',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.line_total' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method_id' => 'required|uuid|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        $transaction = $this->transactionService->createTransaction($validated);

        return $this->success($transaction, 'Transaction created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        return $this->success($transaction);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        $validated = $request->validate([
            'status' => 'nullable|string',
            'contact_id' => 'nullable|uuid',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array',
        ]);

        $updated = $this->transactionService->updateTransaction($transaction, $validated);

        return $this->success($updated, 'Transaction updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        $this->transactionRepository->delete($transaction);

        return $this->success(null, 'Transaction deleted successfully');
    }

    public function complete(string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        $completed = $this->transactionService->completeTransaction($transaction);

        return $this->success($completed, 'Transaction completed successfully');
    }

    public function cancel(string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        $cancelled = $this->transactionService->cancelTransaction($transaction);

        return $this->success($cancelled, 'Transaction cancelled successfully');
    }

    public function addPayment(Request $request, string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return $this->error('Transaction not found', 404);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|uuid|exists:pos_payment_methods,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payment = $this->transactionService->addPayment($transaction, $validated);

        return $this->success($payment, 'Payment added successfully', 201);
    }
}
