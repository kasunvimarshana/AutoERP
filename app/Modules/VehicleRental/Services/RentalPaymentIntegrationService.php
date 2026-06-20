<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalPaymentLink;

final class RentalPaymentIntegrationService
{
    private const LINK_TYPES = ['deposit', 'advance', 'settlement', 'refund'];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceBalanceProviderInterface $invoiceBalances,
        private readonly PaymentCreationService $payments,
    ) {}

    public function prepare(
        RentalAgreement $agreement,
        string $linkType,
        string $paymentDate,
        string $amount,
        ?int $invoiceId = null,
        ?int $paymentMethodId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        ?int $createdBy = null,
    ): CreatePaymentData {
        if (! in_array($linkType, self::LINK_TYPES, true)) {
            throw new InvalidArgumentException('Rental payment link type is invalid.');
        }
        if ($this->math->compare($amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Rental payment amount must be greater than zero.');
        }
        if ($linkType === 'settlement' && $invoiceId === null) {
            throw new InvalidArgumentException('Settlement payments require a linked rental invoice.');
        }

        $invoice = null;
        $allocations = [];
        if ($invoiceId !== null) {
            if (! $agreement->invoiceLinks()->where('invoice_id', $invoiceId)->where('status', 'active')->exists()) {
                throw new InvalidArgumentException('Payment invoice is not linked to this rental agreement.');
            }
            $invoice = Invoice::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->findOrFail($invoiceId);
            $expectedDirection = $agreement->direction === RentalAgreementDirection::Outbound
                ? InvoiceDirection::Outbound
                : InvoiceDirection::Inbound;
            if ($invoice->direction !== $expectedDirection
                || $invoice->party_type !== $this->financialPartyType($agreement)
                || (int) $invoice->party_id !== (int) $agreement->party_id) {
                throw new InvalidArgumentException('Payment invoice does not match the rental agreement party and direction.');
            }
            if ($currencyId !== null && $currencyId !== $invoice->currency_id) {
                throw new InvalidArgumentException('Payment currency must match the rental invoice currency.');
            }
            if ($linkType === 'settlement') {
                $balance = $this->invoiceBalances->validatePayableState($invoiceId);
                if ($this->math->compare($amount, $balance->remainingAmount) > 0) {
                    throw new InvalidArgumentException('Settlement amount cannot exceed the rental invoice balance.');
                }
                $allocations[] = new PaymentAllocationData(
                    invoiceId: $invoiceId,
                    allocatedAmount: $amount,
                    allocationDate: $paymentDate,
                );
            }
        }

        return new CreatePaymentData(
            tenantId: (int) $agreement->tenant_id,
            paymentType: $this->paymentType($agreement, $linkType),
            direction: $this->paymentDirection($agreement, $linkType),
            paymentDate: $paymentDate,
            organizationUnitId: $agreement->organization_unit_id,
            partyType: $this->financialPartyType($agreement),
            partyId: (int) $agreement->party_id,
            sourceType: 'VehicleRentalAgreement',
            sourceId: (int) $agreement->getKey(),
            currencyId: $currencyId ?? $invoice?->currency_id ?? $agreement->currency_id,
            exchangeRate: $exchangeRate,
            referenceNumber: $referenceNumber,
            status: PaymentStatus::Posted,
            notes: str($linkType)->title().' for rental agreement '.$agreement->agreement_number,
            createdBy: $createdBy,
            lines: [new PaymentLineData(
                amount: $amount,
                paymentMethodId: $paymentMethodId,
                referenceNumber: $referenceNumber,
            )],
            allocations: $allocations,
            metadata: [
                'rental_agreement_id' => (int) $agreement->getKey(),
                'rental_link_type' => $linkType,
            ],
        );
    }

    public function create(
        RentalAgreement $agreement,
        string $linkType,
        string $paymentDate,
        string $amount,
        ?int $invoiceId = null,
        ?int $paymentMethodId = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        ?string $referenceNumber = null,
        ?int $createdBy = null,
    ): Payment {
        return DB::transaction(function () use (
            $agreement,
            $linkType,
            $paymentDate,
            $amount,
            $invoiceId,
            $paymentMethodId,
            $currencyId,
            $exchangeRate,
            $referenceNumber,
            $createdBy,
        ): Payment {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $payment = $this->payments->create($this->prepare(
                $agreement,
                $linkType,
                $paymentDate,
                $amount,
                $invoiceId,
                $paymentMethodId,
                $currencyId,
                $exchangeRate,
                $referenceNumber,
                $createdBy,
            ));
            $payment = Payment::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($payment->getKey());
            $this->linkInternal($agreement, $payment, $linkType, $amount, $invoiceId);

            return $payment;
        });
    }

    private function linkInternal(
        RentalAgreement $agreement,
        Payment $payment,
        string $linkType,
        string $amount,
        ?int $invoiceId = null,
    ): RentalPaymentLink {
        if (! in_array($linkType, self::LINK_TYPES, true)) {
            throw new InvalidArgumentException('Rental payment link type is invalid.');
        }
        if ($this->math->compare($amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Rental payment link amount must be greater than zero.');
        }
        if ((int) $payment->tenant_id !== (int) $agreement->tenant_id
            || $payment->organization_unit_id !== $agreement->organization_unit_id) {
            throw new InvalidArgumentException('Rental payment belongs to a different tenant or organization.');
        }
        if ($payment->party_type !== $this->financialPartyType($agreement)
            || (int) $payment->party_id !== (int) $agreement->party_id) {
            throw new InvalidArgumentException('Rental payment party does not match the agreement.');
        }
        if ($payment->direction !== $this->paymentDirection($agreement, $linkType)
            || $payment->payment_type !== $this->paymentType($agreement, $linkType)) {
            throw new InvalidArgumentException('Rental payment direction or type does not match the link purpose.');
        }
        if (in_array($payment->status, [
            PaymentStatus::Void,
            PaymentStatus::Reversed,
            PaymentStatus::Cancelled,
        ], true)) {
            throw new InvalidArgumentException('Void, reversed, or cancelled payments cannot be linked to a rental agreement.');
        }
        if ($this->math->compare($amount, (string) $payment->total_amount) > 0) {
            throw new InvalidArgumentException('Rental payment link amount cannot exceed the payment amount.');
        }
        if (RentalPaymentLink::query()
            ->where('tenant_id', $agreement->tenant_id)
            ->where('organization_unit_id', $agreement->organization_unit_id)
            ->where('agreement_id', $agreement->getKey())
            ->where('payment_id', $payment->getKey())
            ->where('link_type', $linkType)
            ->where('status', 'active')
            ->lockForUpdate()
            ->exists()) {
            throw new InvalidArgumentException('This rental payment is already linked for the same purpose.');
        }
        if ($invoiceId !== null) {
            $invoice = Invoice::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($invoiceId);
            if (! $agreement->invoiceLinks()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->where('invoice_id', $invoiceId)
                ->where('status', 'active')
                ->exists()) {
                throw new InvalidArgumentException('Payment invoice is not linked to this rental agreement.');
            }
            if ($payment->currency_id !== null
                && $invoice->currency_id !== null
                && (int) $payment->currency_id !== (int) $invoice->currency_id) {
                throw new InvalidArgumentException('Rental payment currency must match the linked invoice currency.');
            }
            if ($linkType === 'settlement') {
                $balance = $this->invoiceBalances->validatePayableState($invoiceId);
                if ($this->math->compare($amount, $balance->remainingAmount) > 0) {
                    throw new InvalidArgumentException('Settlement amount cannot exceed the rental invoice balance.');
                }
            }
        } elseif ($linkType === 'settlement') {
            throw new InvalidArgumentException('Settlement payments require a linked rental invoice.');
        }

        return RentalPaymentLink::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice_id' => $invoiceId,
            'link_type' => $linkType,
            'amount' => $this->math->normalize($amount),
            'status' => 'active',
        ]);
    }

    private function paymentDirection(RentalAgreement $agreement, string $linkType): PaymentDirection
    {
        $normal = $agreement->direction === RentalAgreementDirection::Outbound
            ? PaymentDirection::Inbound
            : PaymentDirection::Outbound;

        if ($linkType !== 'refund') {
            return $normal;
        }

        return $normal === PaymentDirection::Inbound
            ? PaymentDirection::Outbound
            : PaymentDirection::Inbound;
    }

    private function paymentType(RentalAgreement $agreement, string $linkType): PaymentType
    {
        if ($linkType === 'refund') {
            return PaymentType::Refund;
        }
        if (in_array($linkType, ['deposit', 'advance'], true)) {
            return PaymentType::Advance;
        }

        return $agreement->direction === RentalAgreementDirection::Outbound
            ? PaymentType::RentalReceipt
            : PaymentType::SupplierPayment;
    }

    private function financialPartyType(RentalAgreement $agreement): string
    {
        return $agreement->party_type === RentalPartyType::Owner
            ? RentalPartyType::Supplier->value
            : $agreement->party_type->value;
    }
}
