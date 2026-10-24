<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;

final class SellingPaymentFinalizationService
{
    private const CUSTOMER_PARTY_TYPE = 'customer';

    private const ZERO_AMOUNT = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PaymentCreationService $creation,
        private readonly PaymentDocumentLifecycleService $lifecycle,
        private readonly PaymentPostingService $posting,
    ) {}

    public function finalize(CreatePaymentData $data): Payment
    {
        $this->assertSellingSettlement($data);

        return DB::transaction(function () use ($data): Payment {
            $payment = $this->creation->create($data);
            if ($payment->document_status === PaymentDocumentStatus::Approved
                && $payment->posting_status === PaymentPostingStatus::Posted) {
                return $payment;
            }

            if ($payment->document_status !== PaymentDocumentStatus::Draft) {
                throw new InvalidArgumentException('Selling payment cannot be finalized from its current status.');
            }

            $payment = $this->lifecycle->transition(
                $payment,
                PaymentDocumentStatus::Submitted,
                (int) $payment->row_version,
                $data->createdBy,
                'Submitted automatically during selling payment finalization.',
            );
            $payment = $this->lifecycle->transition(
                $payment,
                PaymentDocumentStatus::Approved,
                (int) $payment->row_version,
                $data->createdBy,
                'Approved automatically during selling payment finalization.',
            );

            return $this->posting->post($payment, (int) $payment->row_version, $data->createdBy);
        });
    }

    private function assertSellingSettlement(CreatePaymentData $data): void
    {
        if ($data->paymentType !== PaymentType::CustomerReceipt
            || $data->direction !== PaymentDirection::Inbound
            || $data->partyType !== self::CUSTOMER_PARTY_TYPE
            || count($data->allocations) !== 1) {
            throw new InvalidArgumentException('One-click finalization is only available for a customer receipt against one sales invoice.');
        }

        $allocation = $data->allocations[0];
        if ($allocation->allowOverpayment) {
            throw new InvalidArgumentException('Selling payment cannot allow invoice overpayment.');
        }

        $invoice = Invoice::query()->whereKey($allocation->invoiceId)->firstOrFail();
        if ($invoice->invoice_type !== InvoiceType::Sales || (int) $invoice->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('One-click finalization requires a sales invoice in the current tenant.');
        }

        $total = self::ZERO_AMOUNT;
        foreach ($data->lines as $line) {
            $method = PaymentMethod::query()->whereKey($line->paymentMethodId)->firstOrFail();
            if (! in_array($method->method_type, [PaymentMethodType::Cash, PaymentMethodType::Card, PaymentMethodType::BankTransfer], true)) {
                throw new InvalidArgumentException('Selling payment supports cash, card, and bank transfer methods only.');
            }
            $total = $this->math->add($total, $line->amount);
        }

        if ($this->math->compare($total, $allocation->allocatedAmount) !== 0) {
            throw new InvalidArgumentException('Selling payment total must equal the invoice allocation amount.');
        }
    }
}
