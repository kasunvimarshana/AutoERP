<?php

namespace App\Modules\POS\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\POS\DTOs\PaymentDTO;
use App\Modules\POS\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseController
{
    public function __construct(private readonly PaymentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $payments = $this->service->paginate($perPage, ['invoice', 'customer']);

        return $this->success($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'payment_method' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $dto = PaymentDTO::fromRequest($validated);
        $payment = $this->service->create($dto);

        return $this->success($payment, 'Payment recorded successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->service->findOrFail($id, ['invoice', 'customer']);

        return $this->success($payment);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->success(null, 'Payment deleted successfully');
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $payment = $this->service->void($id, $validated['reason']);

        return $this->success($payment, 'Payment voided successfully');
    }

    public function byInvoice(int $invoiceId): JsonResponse
    {
        $payments = $this->service->findByInvoice($invoiceId);

        return $this->success($payments);
    }

    public function byCustomer(Request $request, int $customerId): JsonResponse
    {
        $filters = $request->only(['from_date', 'to_date', 'payment_method']);
        $payments = $this->service->findByCustomer($customerId, $filters);

        return $this->success($payments);
    }
}
