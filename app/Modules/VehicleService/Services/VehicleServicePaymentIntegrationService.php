<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\VehicleService\DTOs\VehicleServicePaymentData;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServicePaymentLink;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServicePaymentIntegrationService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly PaymentCreationService $payments,
        private readonly PaymentDocumentLifecycleService $paymentLifecycle,
        private readonly PaymentPostingService $paymentPosting,
        private readonly VehicleServiceStatusService $statuses,
    ) {}

    public function prepare(VehicleServiceJob $job, VehicleServicePaymentData $data): CreatePaymentData
    {
        $this->assertExpectedVersion($job, $data->expectedVersion);
        $billToCustomerId = $this->billToCustomerId($job);
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

        $balance = $this->invoiceBalances->validatePayableState($data->invoiceId);
        if ($balance->tenantId !== (int) $job->tenant_id
            || $balance->organizationUnitId !== $job->organization_unit_id
            || $balance->partyType !== 'customer'
            || $balance->partyId !== $billToCustomerId) {
            throw new InvalidArgumentException('Payment invoice does not match the service job bill-to customer and scope.');
        }
        if ($data->currencyId !== null && $balance->currencyId !== null && $data->currencyId !== $balance->currencyId) {
            throw new InvalidArgumentException('Payment currency must match the invoice currency.');
        }
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
            partyId: $billToCustomerId,
            sourceType: 'vehicle_service_job',
            sourceId: (int) $job->getKey(),
            currencyId: $data->currencyId ?? $balance->currencyId,
            exchangeRate: $data->exchangeRate,
            referenceNumber: $data->referenceNumber,
            notes: 'Received from vehicle service job '.$job->job_number,
            createdBy: $data->createdBy,
            lines: [new PaymentLineData(
                amount: $data->amount,
                paymentMethodId: $data->paymentMethodId,
                referenceNumber: $data->referenceNumber,
                instrumentDirection: 'received',
                externalBankName: $data->externalBankName,
                externalBankBranch: $data->externalBankBranch,
                instrumentNumber: $data->instrumentNumber,
                instrumentDate: $data->instrumentDate,
                metadata: [
                    'vehicle_service_job_id' => (int) $job->getKey(),
                    'invoice_id' => $data->invoiceId,
                ],
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
            $this->assertExpectedVersion($job, $data->expectedVersion);

            $payment = $this->payments->create($this->prepare($job, $data));
            $payment = $this->paymentLifecycle->submit($payment, (int) $payment->row_version, $data->createdBy);
            $payment = $this->paymentLifecycle->approve($payment, (int) $payment->row_version, $data->createdBy);
            $payment = $this->paymentPosting->post($payment, (int) $payment->row_version, $data->createdBy);

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
            $job->forceFill(['row_version' => (int) $job->row_version + 1])->save();
            $this->syncJobStatus($job->refresh(), $data->createdBy);

            return $payment->refresh()->load([
                'lines',
                'allocations',
                'unappliedBalance',
                'lifecycleEvents',
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
            return $this->statuses->change($job, VehicleServiceJobStatus::Paid, $changedBy, expectedVersion: (int) $job->row_version);
        }
        if ($anySettled && $job->status === VehicleServiceJobStatus::Invoiced) {
            return $this->statuses->change($job, VehicleServiceJobStatus::PartiallyPaid, $changedBy, expectedVersion: (int) $job->row_version);
        }

        return $job;
    }

    private function billToCustomerId(VehicleServiceJob $job): int
    {
        return (int) ($job->bill_to_customer_id ?? $job->customer_id);
    }
}
