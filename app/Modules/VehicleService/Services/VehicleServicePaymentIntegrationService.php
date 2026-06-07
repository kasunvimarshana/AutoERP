<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServicePaymentLink;

final class VehicleServicePaymentIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly PaymentCreationService $payments,
        private readonly VehicleServiceStatusService $statuses,
    ) {}

    public function prepare(
        VehicleServiceJob $job,
        int $invoiceId,
        string $paymentDate,
        string $amount,
        ?int $paymentMethodId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        ?int $createdBy = null,
    ): CreatePaymentData {
        if ($this->math->compare($amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
        if ($this->math->compare($exchangeRate, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment exchange rate must be greater than zero.');
        }
        if (! $job->invoiceLinks()->where('invoice_id', $invoiceId)->where('status', 'active')->exists()) {
            throw new InvalidArgumentException('Payment invoice is not linked to this service job.');
        }
        $invoice = Invoice::query()
            ->with('balance')
            ->where('tenant_id', $job->tenant_id)
            ->where('id', $invoiceId)
            ->firstOrFail();
        if ($invoice->organization_unit_id !== $job->organization_unit_id
            || $invoice->direction !== InvoiceDirection::Outbound
            || $invoice->party_type !== 'customer'
            || (int) $invoice->party_id !== (int) $job->customer_id) {
            throw new InvalidArgumentException('Payment invoice does not match the service job customer and scope.');
        }
        if ($currencyId !== null && $currencyId !== $invoice->currency_id) {
            throw new InvalidArgumentException('Payment currency must match the invoice currency.');
        }

        $balance = $this->invoiceBalances->validatePayableState($invoiceId);
        if ($this->math->compare($balance->remainingAmount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment invoice has no outstanding balance.');
        }
        if ($this->math->compare($amount, $balance->remainingAmount) > 0) {
            throw new InvalidArgumentException('Payment amount cannot exceed invoice remaining balance.');
        }

        return new CreatePaymentData(
            tenantId: (int) $job->tenant_id,
            paymentType: PaymentType::ServiceReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: $paymentDate,
            organizationUnitId: $job->organization_unit_id,
            partyType: 'customer',
            partyId: (int) $job->customer_id,
            currencyId: $currencyId ?? $invoice->currency_id,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            notes: 'Prepared from vehicle service job '.$job->job_number,
            createdBy: $createdBy,
            lines: [new PaymentLineData($amount, paymentMethodId: $paymentMethodId, referenceNumber: $referenceNumber)],
            allocations: [new PaymentAllocationData(
                invoiceId: $invoiceId,
                allocatedAmount: $amount,
                allocationDate: $paymentDate,
            )],
        );
    }

    public function create(
        VehicleServiceJob $job,
        int $invoiceId,
        string $paymentDate,
        string $amount,
        ?int $paymentMethodId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        ?int $createdBy = null,
    ): Payment {
        return DB::transaction(function () use ($job, $invoiceId, $paymentDate, $amount, $paymentMethodId, $currencyId, $exchangeRate, $referenceNumber, $createdBy): Payment {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $payment = $this->payments->create($this->prepare(
                $job,
                $invoiceId,
                $paymentDate,
                $amount,
                $paymentMethodId,
                $currencyId,
                $exchangeRate,
                $referenceNumber,
                $createdBy,
            ));

            VehicleServicePaymentLink::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoiceId,
                'allocated_amount' => $this->math->normalize($amount),
                'status' => 'active',
            ]);
            $this->syncJobStatus($job->refresh(), $createdBy);

            return $payment;
        });
    }

    public function syncJobStatus(VehicleServiceJob $job, ?int $changedBy = null): VehicleServiceJob
    {
        if (! in_array($job->status, [
            VehicleServiceJobStatus::Invoiced,
            VehicleServiceJobStatus::PartiallyPaid,
            VehicleServiceJobStatus::Paid,
        ], true)) {
            return $job;
        }

        $links = $job->invoiceLinks()
            ->where('status', 'active')
            ->with('invoice.balance')
            ->get()
            ->filter(fn ($link): bool => $link->invoice !== null && ! in_array($link->invoice->status, [
                InvoiceStatus::Cancelled,
                InvoiceStatus::Void,
            ], true));
        if ($links->isEmpty()) {
            return $job;
        }

        $allSettled = $links->every(fn ($link): bool => $this->math->compare(
            (string) $link->invoice->balance?->remaining_amount,
            '0.000000',
        ) <= 0);
        $anySettled = $links->contains(fn ($link): bool => $this->math->compare(
            (string) $link->invoice->balance?->paid_amount,
            '0.000000',
        ) > 0 || $this->math->compare(
            (string) $link->invoice->balance?->credit_allocated_amount,
            '0.000000',
        ) > 0);

        if ($allSettled && $job->status !== VehicleServiceJobStatus::Paid) {
            return $this->statuses->change($job, VehicleServiceJobStatus::Paid, $changedBy);
        }
        if ($anySettled && $job->status === VehicleServiceJobStatus::Invoiced) {
            return $this->statuses->change($job, VehicleServiceJobStatus::PartiallyPaid, $changedBy);
        }

        return $job;
    }
}
