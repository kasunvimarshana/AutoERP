<?php

namespace App\Modules\POS\Services;

use App\Modules\POS\DTOs\PaymentDTO;
use App\Modules\POS\Events\PaymentReceived;
use App\Modules\POS\Models\Payment;
use App\Modules\POS\Repositories\PaymentRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService extends BaseService
{
    public function __construct(
        protected PaymentRepository $repository,
        private readonly InvoiceService $invoiceService
    ) {}

    public function create(PaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($dto) {
            $payment = $this->repository->create([
                ...$dto->toArray(),
                'tenant_id' => auth()->user()->tenant_id,
                'payment_number' => $this->repository->generatePaymentNumber(),
            ]);

            // Update invoice payment status
            $this->invoiceService->updatePaymentStatus($dto->invoiceId);

            // Dispatch event for async processing
            event(new PaymentReceived($payment));

            return $payment->load(['invoice', 'customer']);
        });
    }

    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->repository->findByInvoice($invoiceId);
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        return $this->repository->findByCustomer($customerId, $filters);
    }

    public function void(int $id, string $reason): Payment
    {
        return DB::transaction(function () use ($id, $reason) {
            $payment = $this->repository->findOrFail($id);

            // Soft delete the payment
            $payment->update([
                'notes' => ($payment->notes ?? '')."\n\nVOIDED: {$reason}",
            ]);
            $payment->delete();

            // Update invoice payment status
            $this->invoiceService->updatePaymentStatus($payment->invoice_id);

            return $payment;
        });
    }
}
