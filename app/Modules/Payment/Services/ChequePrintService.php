<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\ChequePrintStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\ChequePrintLog;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;

final class ChequePrintService
{
    private const PRINTABLE_STATUSES = [
        PaymentStatus::Approved,
        PaymentStatus::Posted,
        PaymentStatus::PartiallyAllocated,
        PaymentStatus::Allocated,
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly AmountInWordsService $amountInWords,
    ) {}

    /**
     * @return array{payment: array<string, mixed>, template: array<string, mixed>}
     */
    public function preview(Payment $payment, ChequeTemplate $template): array
    {
        $this->assertPrintable($payment, $template);

        $chequeDate = $payment->cheque_date ?? $payment->payment_date;
        $dateFormat = (string) ($template->metadata['date_format'] ?? 'Y-m-d');
        $formattedDate = $chequeDate instanceof CarbonInterface
            ? $chequeDate->format($dateFormat)
            : (string) $chequeDate;
        $amount = (string) $payment->total_amount;
        $words = $this->amountInWords->convert($amount);

        return [
            'payment' => [
                'id' => (int) $payment->getKey(),
                'payment_number' => (string) $payment->payment_number,
                'payment_method' => PaymentMethodType::Cheque->value,
                'payee_name' => (string) $payment->payee_name,
                'amount' => $amount,
                'amount_in_words' => $words,
                'cheque_number' => $payment->cheque_number,
                'cheque_date' => $chequeDate instanceof CarbonInterface ? $chequeDate->toDateString() : $chequeDate,
                'formatted_cheque_date' => $formattedDate,
                'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : (string) $payment->status,
            ],
            'template' => $template->toArray(),
        ];
    }

    public function markPrinted(
        Payment $payment,
        ChequeTemplate $template,
        ?int $printedBy = null,
        ?string $notes = null,
    ): ChequePrintLog {
        $this->assertPrintable($payment, $template);

        return ChequePrintLog::query()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'payment_id' => $payment->getKey(),
            'cheque_template_id' => $template->getKey(),
            'printed_by' => $printedBy,
            'printed_at' => now(),
            'print_status' => ChequePrintStatus::Printed->value,
            'notes' => $notes,
        ]);
    }

    private function assertPrintable(Payment $payment, ChequeTemplate $template): void
    {
        if ((int) $payment->tenant_id !== (int) $template->tenant_id
            || ($template->organization_unit_id !== null
                && $payment->organization_unit_id !== $template->organization_unit_id)) {
            throw new InvalidArgumentException('Cheque template scope must match the payment scope.');
        }

        if (! (bool) $template->is_active) {
            throw new InvalidArgumentException('Cheque template must be active.');
        }

        $status = $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);
        if (! in_array($status, self::PRINTABLE_STATUSES, true)) {
            throw new InvalidArgumentException('Only approved or posted cheque payments can be printed.');
        }

        $payment->loadMissing('lines.paymentMethod');
        if ($payment->lines->isEmpty() || $payment->lines->contains(function ($line): bool {
            $type = $line->paymentMethod?->method_type;
            $value = $type instanceof PaymentMethodType ? $type->value : (string) $type;

            return $value !== PaymentMethodType::Cheque->value;
        })) {
            throw new InvalidArgumentException('Payment method must be cheque.');
        }

        if (trim((string) $payment->payee_name) === '') {
            throw new InvalidArgumentException('Cheque payment must have a payee name.');
        }

        if ($this->math->compare((string) $payment->total_amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Cheque payment amount must be greater than zero.');
        }

        if ($payment->bank_account_id !== null) {
            $payment->loadMissing('bankAccount');
            $bankAccount = $payment->bankAccount;
            if ($bankAccount === null
                || ! (bool) $bankAccount->is_bank_account
                || (int) $bankAccount->tenant_id !== (int) $payment->tenant_id
                || ($bankAccount->organization_unit_id !== null
                    && $bankAccount->organization_unit_id !== $payment->organization_unit_id)) {
                throw new InvalidArgumentException('Cheque bank account must match the payment scope.');
            }
        }
    }
}
