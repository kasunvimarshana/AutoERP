<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use DateTimeInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentRefundData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Payment\Services\PaymentRefundWorkflowService;
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
        private readonly PaymentDocumentLifecycleService $paymentLifecycle,
        private readonly PaymentPostingService $paymentPosting,
        private readonly PaymentAllocationService $allocations,
        private readonly PaymentRefundWorkflowService $refunds,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
    ) {}

    public function receive(RentalDepositRequirement $requirement, array $data, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement, (int) $data['expected_requirement_version']);
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
                paymentDate: (string) $data['payment_date'],
                organizationUnitId: $requirement->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $requirement->agreement->customer_id,
                sourceType: 'rental_deposit_requirement',
                sourceId: (int) $requirement->getKey(),
                currencyId: (int) $requirement->currency_id,
                exchangeRate: (string) ($data['exchange_rate'] ?? '1.000000'),
                referenceNumber: $data['reference_number'] ?? null,
                notes: $data['notes'] ?? 'Rental security deposit',
                createdBy: $userId,
                lines: [new PaymentLineData(
                    amount: $amount,
                    paymentMethodId: (int) $data['payment_method_id'],
                    referenceNumber: $data['reference_number'] ?? null,
                    instrumentDirection: 'received',
                    externalBankName: $data['external_bank_name'] ?? null,
                    externalBankBranch: $data['external_bank_branch'] ?? null,
                    instrumentNumber: $data['instrument_number'] ?? null,
                    instrumentDate: $data['instrument_date'] ?? null,
                )],
                metadata: [
                    'rental_agreement_id' => (int) $requirement->agreement_id,
                    'deposit_requirement_id' => (int) $requirement->getKey(),
                ],
            ));
            $payment = $this->paymentLifecycle->submit($payment, (int) $payment->row_version, $userId);
            $payment = $this->paymentLifecycle->approve($payment, (int) $payment->row_version, $userId);
            $payment = $this->paymentPosting->post($payment, (int) $payment->row_version, $userId);

            $this->link($requirement, RentalDepositLinkType::Receipt, $amount, $userId, paymentId: (int) $payment->getKey());
            $this->sync($requirement);

            return $this->reload($requirement);
        });
    }

    public function applyToInvoice(
        RentalDepositRequirement $requirement,
        Payment $depositPayment,
        int $invoiceId,
        string $amount,
        string $allocationDate,
        int $expectedRequirementVersion,
        int $expectedPaymentVersion,
        ?int $userId,
    ): RentalDepositRequirement {
        return DB::transaction(function () use (
            $requirement,
            $depositPayment,
            $invoiceId,
            $amount,
            $allocationDate,
            $expectedRequirementVersion,
            $expectedPaymentVersion,
            $userId,
        ): RentalDepositRequirement {
            $requirement = $this->locked($requirement, $expectedRequirementVersion);
            $this->assertCustomerRequirement($requirement);
            $depositPayment = Payment::query()->lockForUpdate()->findOrFail($depositPayment->getKey());
            $this->assertReceiptForRequirement($requirement, $depositPayment, $expectedPaymentVersion);
            $amount = $this->positive($amount);
            if ($this->math->compare($amount, $this->availableToApply($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit application exceeds the unapplied deposit balance.');
            }

            $invoice = $this->invoiceBalances->validatePayableState(
                invoiceId: $invoiceId,
                tenantId: (int) $requirement->tenant_id,
                organizationUnitId: $requirement->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $requirement->agreement->customer_id,
                currencyId: (int) $requirement->currency_id,
            );
            if ($invoice->tenantId !== (int) $requirement->tenant_id
                || $invoice->organizationUnitId !== $requirement->organization_unit_id
                || $invoice->partyType !== 'customer'
                || $invoice->partyId !== (int) $requirement->agreement->customer_id
                || ($invoice->currencyId !== null && $invoice->currencyId !== (int) $requirement->currency_id)) {
                throw new InvalidArgumentException('Deposit invoice must belong to the rental customer and currency.');
            }

            $depositPayment = $this->allocations->allocate(
                $depositPayment,
                [new PaymentAllocationData(
                    invoiceId: $invoiceId,
                    allocatedAmount: $amount,
                    allocationDate: $allocationDate,
                    allocationMethod: 'specific_invoice',
                    metadata: ['deposit_requirement_id' => (int) $requirement->getKey()],
                )],
                $expectedPaymentVersion,
                $userId,
            );
            $this->link(
                $requirement,
                RentalDepositLinkType::AppliedToInvoice,
                $amount,
                $userId,
                paymentId: (int) $depositPayment->getKey(),
                invoiceId: $invoiceId,
            );
            $this->sync($requirement);

            return $this->reload($requirement);
        });
    }

    public function refund(RentalDepositRequirement $requirement, Payment $depositPayment, array $data, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($requirement, $depositPayment, $data, $userId): RentalDepositRequirement {
            $requirement = $this->locked($requirement, (int) $data['expected_requirement_version']);
            $this->assertCustomerRequirement($requirement);
            if (! $requirement->is_refundable) {
                throw new InvalidArgumentException('This security deposit is not refundable.');
            }
            $depositPayment = Payment::query()->lockForUpdate()->findOrFail($depositPayment->getKey());
            $this->assertReceiptForRequirement($requirement, $depositPayment, (int) $data['expected_payment_version']);
            $amount = $this->positive((string) $data['amount']);
            if ($this->math->compare($amount, $this->availableToRefund($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit refund exceeds the refundable balance.');
            }

            $refund = $this->refunds->refund(new PaymentRefundData(
                paymentId: (int) $depositPayment->getKey(),
                expectedVersion: (int) $data['expected_payment_version'],
                refundDate: (string) $data['refund_date'],
                amount: $amount,
                paymentMethodId: isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
                referenceNumber: $data['reference_number'] ?? null,
                externalBankName: $data['external_bank_name'] ?? null,
                externalBankBranch: $data['external_bank_branch'] ?? null,
                instrumentNumber: $data['instrument_number'] ?? null,
                instrumentDate: $data['instrument_date'] ?? null,
                reason: (string) $data['reason'],
                refundedBy: $userId,
            ));
            $this->link(
                $requirement,
                RentalDepositLinkType::Refund,
                $amount,
                $userId,
                paymentId: (int) $refund->refund_payment_id,
            );
            $this->sync($requirement);

            return $this->reload($requirement);
        });
    }

    public function forfeit(
        RentalDepositRequirement $requirement,
        Payment $depositPayment,
        int $invoiceId,
        string $amount,
        string $allocationDate,
        int $expectedRequirementVersion,
        int $expectedPaymentVersion,
        ?int $userId,
    ): RentalDepositRequirement {
        return DB::transaction(function () use (
            $requirement,
            $depositPayment,
            $invoiceId,
            $amount,
            $allocationDate,
            $expectedRequirementVersion,
            $expectedPaymentVersion,
            $userId,
        ): RentalDepositRequirement {
            $requirement = $this->locked($requirement, $expectedRequirementVersion);
            $this->assertCustomerRequirement($requirement);
            $depositPayment = Payment::query()->lockForUpdate()->findOrFail($depositPayment->getKey());
            $this->assertReceiptForRequirement($requirement, $depositPayment, $expectedPaymentVersion);
            $amount = $this->positive($amount);
            if ($this->math->compare($amount, $this->availableToRefund($requirement)) > 0) {
                throw new InvalidArgumentException('Deposit forfeiture exceeds the uncommitted deposit balance.');
            }
            $invoice = $this->invoiceBalances->validatePayableState(
                invoiceId: $invoiceId,
                tenantId: (int) $requirement->tenant_id,
                organizationUnitId: $requirement->organization_unit_id,
                partyType: 'customer',
                partyId: (int) $requirement->agreement->customer_id,
                currencyId: (int) $requirement->currency_id,
            );
            if ($invoice->tenantId !== (int) $requirement->tenant_id
                || $invoice->organizationUnitId !== $requirement->organization_unit_id
                || $invoice->partyType !== 'customer'
                || $invoice->partyId !== (int) $requirement->agreement->customer_id
                || ($invoice->currencyId !== null && $invoice->currencyId !== (int) $requirement->currency_id)) {
                throw new InvalidArgumentException('Forfeiture invoice must belong to the rental customer and currency.');
            }
            $depositPayment = $this->allocations->allocate(
                $depositPayment,
                [new PaymentAllocationData(
                    invoiceId: $invoiceId,
                    allocatedAmount: $amount,
                    allocationDate: $allocationDate,
                    allocationMethod: 'specific_invoice',
                    metadata: [
                        'deposit_requirement_id' => (int) $requirement->getKey(),
                        'deposit_movement' => RentalDepositLinkType::Forfeiture->value,
                    ],
                )],
                $expectedPaymentVersion,
                $userId,
            );
            $this->link(
                $requirement,
                RentalDepositLinkType::Forfeiture,
                $amount,
                $userId,
                paymentId: (int) $depositPayment->getKey(),
                invoiceId: $invoiceId,
            );
            $this->sync($requirement);

            return $this->reload($requirement);
        });
    }

    public function reverseLink(RentalDepositLink $link, int $expectedRequirementVersion, ?int $userId): RentalDepositRequirement
    {
        return DB::transaction(function () use ($link, $expectedRequirementVersion, $userId): RentalDepositRequirement {
            $link = RentalDepositLink::query()->with('requirement.agreement')->lockForUpdate()->findOrFail($link->getKey());
            $requirement = $this->locked($link->requirement, $expectedRequirementVersion);
            if ($link->status !== 'active') {
                throw new InvalidArgumentException('Only an active deposit link can be reversed.');
            }
            if ($link->link_type === RentalDepositLinkType::Reversal) {
                throw new InvalidArgumentException('A deposit reversal link cannot be reversed again.');
            }
            if ($link->payment_id !== null) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($link->payment_id);
                $document = $payment->document_status instanceof PaymentDocumentStatus
                    ? $payment->document_status
                    : PaymentDocumentStatus::from((string) $payment->document_status);
                if (! in_array($document, [PaymentDocumentStatus::Voided, PaymentDocumentStatus::Reversed], true)) {
                    throw new InvalidArgumentException('Void or reverse the linked payment before reversing this deposit movement.');
                }
            }
            if ($link->invoice_id !== null) {
                $activeAllocation = DB::table('payment_allocations')
                    ->where('tenant_id', $link->tenant_id)
                    ->where('payment_id', $link->payment_id)
                    ->where('invoice_id', $link->invoice_id)
                    ->where('status', 'active')
                    ->exists();
                if ($activeAllocation) {
                    throw new InvalidArgumentException('Reverse the linked payment allocation before reversing this deposit movement.');
                }
            }

            $link->forceFill([
                'status' => 'reversed',
                'row_version' => (int) $link->row_version + 1,
                'updated_by' => $userId,
            ])->save();
            $this->link($requirement, RentalDepositLinkType::Reversal, (string) $link->amount, $userId, reversesLinkId: (int) $link->getKey());
            $this->sync($requirement);

            return $this->reload($requirement);
        });
    }

    private function locked(RentalDepositRequirement $requirement, int $expectedVersion): RentalDepositRequirement
    {
        $locked = RentalDepositRequirement::query()->with(['agreement', 'links'])->lockForUpdate()->findOrFail($requirement->getKey());
        if ($expectedVersion < 1 || (int) $locked->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Deposit requirement was changed by another request. Reload it before continuing.');
        }

        return $locked;
    }

    private function assertCustomerRequirement(RentalDepositRequirement $requirement): void
    {
        if ($requirement->agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException('Security deposit is supported only for customer rental agreements.');
        }
    }

    private function assertReceiptForRequirement(RentalDepositRequirement $requirement, Payment $payment, int $expectedVersion): void
    {
        if ((int) $payment->row_version !== $expectedVersion
            || (int) $payment->tenant_id !== (int) $requirement->tenant_id
            || $payment->organization_unit_id !== $requirement->organization_unit_id
            || (int) $payment->party_id !== (int) $requirement->agreement->customer_id
            || (int) $payment->currency_id !== (int) $requirement->currency_id
            || (string) $payment->source_type !== 'rental_deposit_requirement'
            || (int) $payment->source_id !== (int) $requirement->getKey()) {
            throw new InvalidArgumentException('Selected payment is not the expected rental deposit receipt.');
        }
        $document = $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
        $posting = $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
        if ($document !== PaymentDocumentStatus::Approved || $posting !== PaymentPostingStatus::Posted) {
            throw new InvalidArgumentException('Rental deposit receipt must be approved and posted.');
        }
        if (! $requirement->links()
            ->where('link_type', RentalDepositLinkType::Receipt->value)
            ->where('payment_id', $payment->getKey())
            ->where('status', 'active')
            ->exists()) {
            throw new InvalidArgumentException('Selected payment is not linked as an active deposit receipt.');
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
        $linkedAt = now();

        return $requirement->links()->create([
            'tenant_id' => $requirement->tenant_id,
            'organization_unit_id' => $requirement->organization_unit_id,
            'link_type' => $type->value,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'status' => 'active',
            'linked_at' => $linkedAt,
            'linked_by' => $userId,
            'reverses_link_id' => $reversesLinkId,
            'fingerprint' => $this->linkFingerprint($requirement, $type, $amount, $paymentId, $invoiceId, $reversesLinkId, $linkedAt),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function linkFingerprint(
        RentalDepositRequirement $requirement,
        RentalDepositLinkType $type,
        string $amount,
        ?int $paymentId,
        ?int $invoiceId,
        ?int $reversesLinkId,
        DateTimeInterface $linkedAt,
    ): string {
        return hash('sha256', implode('|', [
            (int) $requirement->tenant_id,
            (int) $requirement->getKey(),
            $type->value,
            $this->math->normalize($amount),
            $paymentId ?? '',
            $invoiceId ?? '',
            $reversesLinkId ?? '',
            $linkedAt->format('Y-m-d H:i:s.u'),
        ]));
    }

    private function sync(RentalDepositRequirement $requirement): void
    {
        $links = $requirement->links()->where('status', 'active')->get();
        $received = $this->math->sum($links->where('link_type', RentalDepositLinkType::Receipt)->pluck('amount')->map(fn ($value) => (string) $value)->all());
        $applied = $this->math->sum($links->where('link_type', RentalDepositLinkType::AppliedToInvoice)->pluck('amount')->map(fn ($value) => (string) $value)->all());
        $refunded = $this->math->sum($links->where('link_type', RentalDepositLinkType::Refund)->pluck('amount')->map(fn ($value) => (string) $value)->all());
        $forfeited = $this->math->sum($links->where('link_type', RentalDepositLinkType::Forfeiture)->pluck('amount')->map(fn ($value) => (string) $value)->all());
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
            'metadata' => array_merge($requirement->metadata ?? [], [
                'remaining_required_amount' => $this->math->isNegative($unreceived) ? '0.000000' : $unreceived,
            ]),
            'row_version' => (int) $requirement->row_version + 1,
        ])->save();
    }

    private function reload(RentalDepositRequirement $requirement): RentalDepositRequirement
    {
        return $requirement->refresh()->load(['agreement.customer', 'currency', 'links.payment', 'links.invoice', 'links.reversesLink']);
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
