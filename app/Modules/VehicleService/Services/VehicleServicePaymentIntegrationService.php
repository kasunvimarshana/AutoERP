<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Payment\Services\PaymentStatusService;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServicePaymentLink;

final class VehicleServicePaymentIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly PaymentCreationService $payments,
        private readonly PaymentStatusService $paymentStatuses,
        private readonly PaymentPostingService $paymentPosting,
        private readonly VehicleServiceStatusService $statuses,
    ) {}

    public function prepare(VehicleServiceJob $job, VehicleServicePaymentData $data): CreatePaymentData
    {
        if ($this->math->compare($data->amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }
        if ($this->math->compare($data->exchangeRate, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment exchange rate must be greater than zero.');
        }
        if (! $job->invoiceLinks()
            ->where('invoice_id', $data->invoiceId)
            ->where('status', 'active')
            ->exists()) {
            throw new InvalidArgumentException('Payment invoice is not linked to this service job.');
        }

        $invoice = Invoice::query()
            ->with('balance')
            ->where('tenant_id', $job->tenant_id)
            ->where('id', $data->invoiceId)
            ->firstOrFail();
        if ($invoice->organization_unit_id !== $job->organization_unit_id
            || $invoice->direction !== InvoiceDirection::Outbound
            || $invoice->party_type !== 'customer'
            || (int) $invoice->party_id !== (int) $job->customer_id) {
            throw new InvalidArgumentException('Payment invoice does not match the service job customer and scope.');
        }
        if ($data->currencyId !== null && $data->currencyId !== $invoice->currency_id) {
            throw new InvalidArgumentException('Payment currency must match the invoice currency.');
        }

        $balance = $this->invoiceBalances->validatePayableState($data->invoiceId);
        if ($this->math->compare($balance->remainingAmount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Payment invoice has no outstanding balance.');
        }
        if ($this->math->compare($data->amount, $balance->remainingAmount) > 0) {
            throw new InvalidArgumentException('Payment amount cannot exceed invoice remaining balance.');
        }

        return new CreatePaymentData(
            tenantId: (int) $job->tenant_id,
            paymentType: PaymentType::ServiceReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: $data->paymentDate,
            organizationUnitId: $job->organization_unit_id,
            partyType: 'customer',
            partyId: (int) $job->customer_id,
            sourceType: 'vehicle_service_job',
            sourceId: (int) $job->getKey(),
            currencyId: $data->currencyId ?? $invoice->currency_id,
            exchangeRate: $data->exchangeRate,
            referenceNumber: $data->referenceNumber,
            notes: 'Received from vehicle service job '.$job->job_number,
            createdBy: $data->createdBy,
            lines: [new PaymentLineData(
                amount: $data->amount,
                paymentMethodId: $data->paymentMethodId,
                referenceNumber: $data->referenceNumber,
                metadata: array_merge($data->metadata ?? [], [
                    'vehicle_service_job_id' => (int) $job->getKey(),
                    'invoice_id' => $data->invoiceId,
                ]),
                internalBankAccountId: $data->internalBankAccountId,
                instrumentDirection: 'received',
                externalBankName: $data->externalBankName,
                externalBankBranch: $data->externalBankBranch,
                instrumentNumber: $data->instrumentNumber,
                instrumentDate: $data->instrumentDate,
                depositDate: $data->depositDate,
                realizedDate: $data->realizedDate,
            )],
            allocations: [new PaymentAllocationData(
                invoiceId: $data->invoiceId,
                allocatedAmount: $data->amount,
                allocationDate: $data->paymentDate,
                metadata: [
                    'vehicle_service_job_id' => (int) $job->getKey(),
                    'vehicle_service_job_number' => (string) $job->job_number,
                ],
            )],
            metadata: [
                'vehicle_service_job_id' => (int) $job->getKey(),
                'vehicle_service_job_number' => (string) $job->job_number,
                'invoice_id' => $data->invoiceId,
            ],
        );
    }

    public function create(VehicleServiceJob $job, VehicleServicePaymentData $data): Payment
    {
        return DB::transaction(function () use ($job, $data): Payment {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());

            $payment = $this->payments->create($this->prepare($job, $data));
            $payment = $this->paymentStatuses->transition(
                $payment,
                PaymentStatus::Approved,
                $data->createdBy,
                'Vehicle service receipt approved for immediate posting.',
            );
            $payment = $this->paymentPosting->post($payment, $data->createdBy);

            $allocation = $payment->allocations
                ->first(fn ($row): bool => (int) $row->invoice_id === $data->invoiceId
                    && (string) ($row->status instanceof \BackedEnum ? $row->status->value : $row->status) === AllocationStatus::Active->value);
            if ($allocation === null) {
                throw new LogicException('Posted vehicle service payment did not realize its invoice allocation.');
            }

            VehicleServicePaymentLink::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'payment_id' => $payment->getKey(),
                'invoice_id' => $data->invoiceId,
                'allocated_amount' => $this->math->normalize((string) $allocation->allocated_amount),
                'status' => 'active',
            ]);
            $this->syncJobStatus($job->refresh(), $data->createdBy);

            return $payment->refresh()->load([
                'lines.paymentMethod',
                'lines.internalBankAccount',
                'allocations',
                'unappliedBalance',
                'financeJournalEntry',
            ]);
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
