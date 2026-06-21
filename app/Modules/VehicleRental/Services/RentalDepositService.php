<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalDepositLinkType;
use Modules\VehicleRental\Enums\RentalDepositStatus;
use Modules\VehicleRental\Models\RentalDepositLink;
use Modules\VehicleRental\Models\RentalDepositRequirement;

final class RentalDepositService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $payments,
        private readonly PaymentAllocationService $allocations,
    ) {}

    public function receive(RentalDepositRequirement $requirement, array $data, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement);
            $this->assertCustomerRequirement($requirement);
            $amount = $this->positive((string) $data['amount']);
            $remainingRequired = $this->math->sub((string) $requirement->required_amount, (string) $requirement->received_amount);
            if ($this->math->compare($amount, $remainingRequired) > 0) {
                throw new InvalidArgumentException('Deposit receipt cannot exceed the remaining required deposit.');
            }

            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: (int) $requirement->tenant_id,
                paymentType: PaymentType::Advance,
                direction: PaymentDirection::Inbound,
                paymentDate: $data['payment_date'],
                organizationUnitId: $requirement->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $requirement->agreement->customer_id,
                sourceType: 'rental_deposit_requirement',
                sourceId: (int) $requirement->getKey(),
                currencyId: (int) $requirement->currency_id,
                exchangeRate: $data['exchange_rate'] ?? '1.000000',
                referenceNumber: $data['reference_number'] ?? null,
                status: PaymentStatus::Posted,
                notes: $data['notes'] ?? 'Rental security deposit',
                createdBy: $userId,
                lines: [new PaymentLineData(
                    amount: $amount,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    referenceNumber: $data['reference_number'] ?? null,
                    internalBankAccountId: $data['internal_bank_account_id'] ?? null,
                    instrumentNumber: $data['instrument_number'] ?? null,
                    instrumentDate: $data['instrument_date'] ?? null,
                )],
                metadata: ['rental_agreement_id' => $requirement->agreement_id, 'deposit_requirement_id' => $requirement->getKey()],
            ));

            $this->link($requirement, RentalDepositLinkType::Receipt, $amount, $userId, paymentId: (int) $payment->getKey());
            $this->sync($requirement);

            return $requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        });
    }

    public function applyToInvoice(
        RentalDepositRequirement $requirement,
        Payment $depositPayment,
        Invoice $invoice,
        string $amount,
        string $allocationDate,
        ?int $userId,
    ): RentalDepositRequirement {
        return DB::transaction(function () use ($requirement, $depositPayment, $invoice, $amount, $allocationDate, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement);
            $this->assertCustomerRequirement($requirement);
            $amount = $this->positive($amount);
            if ((int) $depositPayment->tenant_id !== (int) $requirement->tenant_id
                || (int) $depositPayment->party_id !== (int) $requirement->agreement->customer_id
                || (int) $invoice->tenant_id !== (int) $requirement->tenant_id
                || (int) $invoice->party_id !== (int) $requirement->agreement->customer_id
                || (int) $depositPayment->currency_id !== (int) $requirement->currency_id
                || (int) $invoice->currency_id !== (int) $requirement->currency_id
                || $depositPayment->direction !== PaymentDirection::Inbound
                || $invoice->direction !== InvoiceDirection::Outbound
                || in_array($depositPayment->status, [PaymentStatus::Cancelled, PaymentStatus::Reversed, PaymentStatus::Voided, PaymentStatus::Void], true)
                || in_array($invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                throw new InvalidArgumentException('Deposit payment and invoice must belong to the rental customer.');
            }
            if ((string) $depositPayment->source_type !== 'rental_deposit_requirement'
                || (int) $depositPayment->source_id !== (int) $requirement->getKey()) {
                throw new InvalidArgumentException('Selected payment is not a receipt for this deposit requirement.');
            }
            if ($this->math->compare($amount, $this->availableToApply($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit application exceeds the unapplied deposit balance.');
            }

            $this->allocations->allocate($depositPayment, [new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: $amount,
                allocationDate: $allocationDate,
                allocationMethod: 'specific_invoice',
                metadata: ['deposit_requirement_id' => $requirement->getKey()],
            )]);
            $this->link($requirement, RentalDepositLinkType::AppliedToInvoice, $amount, $userId, paymentId: (int) $depositPayment->getKey(), invoiceId: (int) $invoice->getKey());
            $this->sync($requirement);

            return $requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        });
    }

    public function refund(RentalDepositRequirement $requirement, array $data, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement);
            $this->assertCustomerRequirement($requirement);
            if (! $requirement->is_refundable) {
                throw new InvalidArgumentException('This security deposit is not refundable.');
            }
            $amount = $this->positive((string) $data['amount']);
            if ($this->math->compare($amount, $this->availableToRefund($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit refund exceeds the refundable balance.');
            }

            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: (int) $requirement->tenant_id,
                paymentType: PaymentType::Refund,
                direction: PaymentDirection::Outbound,
                paymentDate: $data['payment_date'],
                organizationUnitId: $requirement->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $requirement->agreement->customer_id,
                sourceType: 'rental_deposit_requirement',
                sourceId: (int) $requirement->getKey(),
                currencyId: (int) $requirement->currency_id,
                referenceNumber: $data['reference_number'] ?? null,
                status: PaymentStatus::Posted,
                notes: $data['notes'] ?? 'Rental security deposit refund',
                createdBy: $userId,
                lines: [new PaymentLineData(
                    amount: $amount,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    referenceNumber: $data['reference_number'] ?? null,
                    internalBankAccountId: $data['internal_bank_account_id'] ?? null,
                )],
                metadata: ['rental_agreement_id' => $requirement->agreement_id, 'deposit_requirement_id' => $requirement->getKey()],
            ));
            $this->link($requirement, RentalDepositLinkType::Refund, $amount, $userId, paymentId: (int) $payment->getKey());
            $this->sync($requirement);

            return $requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        });
    }

    public function forfeit(RentalDepositRequirement $requirement, Invoice $invoice, string $amount, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($requirement, $invoice, $amount, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement);
            $this->assertCustomerRequirement($requirement);
            $amount = $this->positive($amount);
            if ($this->math->compare($amount, $this->availableToRefund($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit forfeiture exceeds the uncommitted deposit balance.');
            }
            if ((int) $invoice->tenant_id !== (int) $requirement->tenant_id
                || (int) $invoice->party_id !== (int) $requirement->agreement->customer_id
                || (int) $invoice->currency_id !== (int) $requirement->currency_id
                || $invoice->direction !== InvoiceDirection::Outbound
                || in_array($invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                throw new InvalidArgumentException('Forfeiture invoice must belong to the rental customer.');
            }
            $this->link($requirement, RentalDepositLinkType::Forfeiture, $amount, $userId, invoiceId: (int) $invoice->getKey());
            $this->sync($requirement);

            return $requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        });
    }

    public function reverseLink(RentalDepositLink $link, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($link, $userId): RentalDepositRequirement {
            $link = RentalDepositLink::query()->with('requirement.agreement')->lockForUpdate()->findOrFail($link->getKey());
            if ($link->status !== 'active') {
                throw new InvalidArgumentException('Only an active deposit link can be reversed.');
            }
            if ($link->payment_id !== null) {
                $payment = Payment::query()->findOrFail($link->payment_id);
                if (! in_array($payment->status, [PaymentStatus::Cancelled, PaymentStatus::Reversed, PaymentStatus::Voided, PaymentStatus::Void], true)) {
                    throw new InvalidArgumentException('Reverse or void the linked core payment before reversing this deposit movement.');
                }
            }
            if ($link->invoice_id !== null) {
                $invoice = Invoice::query()->findOrFail($link->invoice_id);
                if (! in_array($invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                    $activeAllocation = DB::table('payment_allocations')
                        ->where('payment_id', $link->payment_id)
                        ->where('invoice_id', $link->invoice_id)
                        ->where('status', 'active')
                        ->exists();
                    if ($activeAllocation) {
                        throw new InvalidArgumentException('Reverse the linked payment allocation or invoice before reversing this deposit movement.');
                    }
                }
            }
            $link->status = 'reversed';
            $link->updated_by = $userId;
            $link->save();
            $this->link($link->requirement, RentalDepositLinkType::Reversal, (string) $link->amount, $userId, reversesLinkId: (int) $link->getKey());
            $this->sync($link->requirement);

            return $link->requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        });
    }

    private function locked(RentalDepositRequirement $requirement): RentalDepositRequirement
    {
        return RentalDepositRequirement::query()->with(['agreement', 'links'])->lockForUpdate()->findOrFail($requirement->getKey());
    }

    private function assertCustomerRequirement(RentalDepositRequirement $requirement): void
    {
        if ($requirement->agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException('Security deposit is supported only for customer rental agreements.');
        }
    }

    private function link(
        RentalDepositRequirement $requirement,
        RentalDepositLinkType $type,
        string $amount,
        ?int $userId,
        ?int $paymentId = null,
        ?int $invoiceId = null,
        ?int $reversesLinkId = null,
    ): RentalDepositLink {
        return $requirement->links()->create([
            'tenant_id' => $requirement->tenant_id,
            'organization_unit_id' => $requirement->organization_unit_id,
            'link_type' => $type->value,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'status' => 'active',
            'linked_at' => now(),
            'linked_by' => $userId,
            'reverses_link_id' => $reversesLinkId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function sync(RentalDepositRequirement $requirement): void
    {
        $links = $requirement->links()->where('status', 'active')->get();
        $received = $this->math->sum($links->where('link_type', RentalDepositLinkType::Receipt)->pluck('amount')->map(fn ($v) => (string) $v)->all());
        $applied = $this->math->sum($links->where('link_type', RentalDepositLinkType::AppliedToInvoice)->pluck('amount')->map(fn ($v) => (string) $v)->all());
        $refunded = $this->math->sum($links->where('link_type', RentalDepositLinkType::Refund)->pluck('amount')->map(fn ($v) => (string) $v)->all());
        $forfeited = $this->math->sum($links->where('link_type', RentalDepositLinkType::Forfeiture)->pluck('amount')->map(fn ($v) => (string) $v)->all());
        $unreceived = $this->math->sub((string) $requirement->required_amount, $received);
        $balance = $this->math->sub($this->math->sub($this->math->sub($received, $applied), $refunded), $forfeited);
        $status = match (true) {
            $this->math->compare($forfeited, '0') > 0 && $this->math->isZero($balance) => RentalDepositStatus::Forfeited,
            $this->math->compare($refunded, '0') > 0 && $this->math->isZero($balance) => RentalDepositStatus::Refunded,
            $this->math->compare($applied, '0') > 0 && $this->math->isZero($balance) => RentalDepositStatus::Closed,
            $this->math->compare($applied, '0') > 0 => RentalDepositStatus::PartiallyApplied,
            $this->math->compare($received, (string) $requirement->required_amount) >= 0 => RentalDepositStatus::Received,
            $this->math->compare($received, '0') > 0 => RentalDepositStatus::PartiallyReceived,
            default => RentalDepositStatus::Pending,
        };
        $requirement->forceFill([
            'received_amount' => $received,
            'applied_amount' => $applied,
            'refunded_amount' => $refunded,
            'forfeited_amount' => $forfeited,
            'balance_amount' => $this->math->isNegative($balance) ? '0.000000' : $balance,
            'status' => $status->value,
            'metadata' => array_merge($requirement->metadata ?? [], ['remaining_required_amount' => $this->math->isNegative($unreceived) ? '0.000000' : $unreceived]),
        ])->save();
    }

    private function availableToApply(RentalDepositRequirement $requirement): string
    {
        return $this->math->sub(
            $this->math->sub((string) $requirement->received_amount, (string) $requirement->applied_amount),
            $this->math->add((string) $requirement->refunded_amount, (string) $requirement->forfeited_amount),
        );
    }

    private function availableToRefund(RentalDepositRequirement $requirement): string
    {
        return $this->availableToApply($requirement);
    }

    private function positive(string $amount): string
    {
        $amount = $this->math->normalize($amount);
        if ($this->math->compare($amount, '0') <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        return $amount;
    }
}
