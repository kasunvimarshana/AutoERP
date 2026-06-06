<?php

declare(strict_types=1);

namespace Modules\Payment\Validators;

use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\DecimalMath;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentMethodService;

final class PaymentValidationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentMethodService $paymentMethods,
    ) {}

    public function validateForCreation(CreatePaymentData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Payment tenant is required.');
        }

        if ($data->organizationUnitId !== null && $data->organizationUnitId < 1) {
            throw new InvalidArgumentException('Payment organization unit must be a positive id.');
        }

        if (trim($data->paymentDate) === '') {
            throw new InvalidArgumentException('Payment date is required.');
        }

        if ($this->math->isNegative($data->exchangeRate) || $this->math->isZero($data->exchangeRate)) {
            throw new InvalidArgumentException('Payment exchange rate must be greater than zero.');
        }

        if ($data->lines === []) {
            throw new InvalidArgumentException('Payment requires at least one payment line.');
        }

        foreach ($data->lines as $line) {
            if (! $line instanceof PaymentLineData) {
                throw new InvalidArgumentException('Payment lines must be PaymentLineData instances.');
            }

            $this->assertPositive($line->amount, 'Payment line amount');
            $this->assertNonNegative($line->clearedAmount, 'Payment line cleared amount');

            $method = $line->paymentMethodId !== null
                ? PaymentMethod::query()->find($line->paymentMethodId)
                : null;
            $this->paymentMethods->assertUsable($method, $data->direction, $line->referenceNumber ?? $data->referenceNumber);
        }

        foreach ($data->allocations as $allocation) {
            if (! $allocation instanceof PaymentAllocationData) {
                throw new InvalidArgumentException('Payment allocations must be PaymentAllocationData instances.');
            }
            $this->assertPositive($allocation->allocatedAmount, 'Payment allocation amount');
        }
    }

    public function validateInvoiceAllocation(Payment $payment, Invoice $invoice, PaymentAllocationData $allocation): void
    {
        if ((int) $payment->tenant_id !== (int) $invoice->tenant_id) {
            throw new InvalidArgumentException('Payment invoice tenant must match payment tenant.');
        }

        if ($payment->organization_unit_id !== $invoice->organization_unit_id) {
            throw new InvalidArgumentException('Payment invoice organization unit must match payment organization unit.');
        }

        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);

        if (in_array($status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
            throw new InvalidArgumentException('Cancelled or void invoices cannot receive payment allocations.');
        }

        $this->assertPositive($allocation->allocatedAmount, 'Payment allocation amount');
    }

    public function assertPositive(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
    }

    public function assertNonNegative(string $amount, string $label): void
    {
        if ($this->math->isNegative($amount)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }
    }
}
