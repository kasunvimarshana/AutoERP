<?php
namespace Modules\Accounting\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Accounting\Application\Contracts\ProcessPaymentServiceInterface;
use Modules\Accounting\Application\DTOs\PaymentData;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\Events\PaymentCompleted;
use Modules\Accounting\Domain\Events\PaymentCreated;
use Modules\Accounting\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Accounting\Domain\ValueObjects\PaymentStatus;

class ProcessPaymentService implements ProcessPaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function execute(PaymentData $data): Payment
    {
        $payment = $this->paymentRepository->create([
            'tenant_id'        => $data->tenantId,
            'reference_number' => $data->referenceNumber,
            'status'           => PaymentStatus::PENDING,
            'method'           => $data->method,
            'amount'           => $data->amount,
            'currency'         => $data->currency,
            'payable_type'     => $data->payableType,
            'payable_id'       => $data->payableId,
            'paid_by'          => $data->paidBy,
            'notes'            => $data->notes,
        ]);

        $this->dispatcher->dispatch(new PaymentCreated($payment->tenantId, $payment->id));

        $payment = $this->paymentRepository->update($payment, [
            'status'  => PaymentStatus::COMPLETED,
            'paid_at' => now()->toDateTimeString(),
        ]);

        $this->dispatcher->dispatch(new PaymentCompleted($payment->tenantId, $payment->id));

        return $payment;
    }
}
