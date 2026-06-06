<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Models\Payment;

final class InvoicePaymentIntegrationService
{
    public function __construct(private readonly PaymentAllocationService $allocations) {}

    /**
     * @param  list<PaymentAllocationData>  $allocations
     */
    public function allocatePaymentToInvoices(Payment $payment, array $allocations): Payment
    {
        return $this->allocations->allocate($payment, $allocations);
    }
}
