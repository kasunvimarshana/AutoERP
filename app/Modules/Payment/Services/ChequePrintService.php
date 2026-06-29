<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\ChequePrintStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Models\ChequePrintLog;
use Modules\Payment\Models\ChequeTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;

final class ChequePrintService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly AmountInWordsService $amountInWords,
    ) {}

    public function preview(Payment $payment, PaymentLine $line, ChequeTemplate $template): array
    {
        $this->assertPrintable($payment, $line, $template);
        $chequeDate = $line->instrument_date ?? $payment->cheque_date ?? $payment->payment_date;
        $dateFormat = (string) ($template->metadata['date_format'] ?? 'Y-m-d');
        $formattedDate = $chequeDate instanceof CarbonInterface ? $chequeDate->format($dateFormat) : (string) $chequeDate;
        $amount = (string) $line->amount;
        $metadata = is_array($line->metadata) ? $line->metadata : [];
        $payeeName = (string) ($metadata['payee_name'] ?? $payment->payee_name);

        return [
            'payment' => [
                'id' => (int) $payment->getKey(),
                'payment_number' => (string) $payment->payment_number,
                'document_status' => $this->documentStatus($payment)->value,
            ],
            'line' => [
                'id' => (int) $line->getKey(),
                'payment_method' => [
                    'code' => $line->payment_method_code_snapshot,
                    'name' => $line->payment_method_name_snapshot,
                    'method_type' => $line->payment_method_type_snapshot,
                ],
                'payee_name' => $payeeName,
                'amount' => $amount,
                'amount_in_words' => $this->amountInWords->convert($amount),
                'cheque_number' => $line->instrument_number ?? $payment->cheque_number,
                'cheque_date' => $chequeDate instanceof CarbonInterface ? $chequeDate->toDateString() : $chequeDate,
                'formatted_cheque_date' => $formattedDate,
                'external_bank_name' => $line->external_bank_name,
                'external_bank_branch' => $line->external_bank_branch,
            ],
            'template' => $template->toArray(),
        ];
    }

    public function markPrinted(
        Payment $payment,
        PaymentLine $line,
        ChequeTemplate $template,
        ?int $printedBy = null,
        ?string $notes = null,
    ): ChequePrintLog {
        $this->assertPrintable($payment, $line, $template);

        return ChequePrintLog::query()->create([
            'tenant_id' => $payment->tenant_id,
            'organization_unit_id' => $payment->organization_unit_id,
            'payment_id' => $payment->getKey(),
            'payment_line_id' => $line->getKey(),
            'cheque_template_id' => $template->getKey(),
            'printed_by' => $printedBy,
            'printed_at' => now(),
            'print_status' => ChequePrintStatus::Printed->value,
            'notes' => $notes,
        ]);
    }

    private function assertPrintable(Payment $payment, PaymentLine $line, ChequeTemplate $template): void
    {
        if ((int) $line->payment_id !== (int) $payment->getKey()) {
            throw new InvalidArgumentException('Cheque line must belong to the selected payment.');
        }
        if ((int) $payment->tenant_id !== (int) $template->tenant_id
            || ($template->organization_unit_id !== null
                && $payment->organization_unit_id !== $template->organization_unit_id)) {
            throw new InvalidArgumentException('Cheque template scope must match the payment scope.');
        }
        if (! (bool) $template->is_active) {
            throw new InvalidArgumentException('Cheque template must be active.');
        }
        if ($this->documentStatus($payment) !== PaymentDocumentStatus::Approved) {
            throw new InvalidArgumentException('Only approved cheque payments can be printed.');
        }
        if ((string) $line->payment_method_type_snapshot !== PaymentMethodType::Cheque->value) {
            throw new InvalidArgumentException('Selected payment line is not cheque-capable.');
        }

        $metadata = is_array($line->metadata) ? $line->metadata : [];
        if (trim((string) ($metadata['payee_name'] ?? $payment->payee_name)) === '') {
            throw new InvalidArgumentException('Cheque line requires a payee name.');
        }
        if (trim((string) ($line->instrument_number ?? $payment->cheque_number)) === '') {
            throw new InvalidArgumentException('Cheque line requires an instrument number.');
        }
        if ($this->math->compare((string) $line->amount, '0.000000') <= 0) {
            throw new InvalidArgumentException('Cheque line amount must be greater than zero.');
        }
    }

    private function documentStatus(Payment $payment): PaymentDocumentStatus
    {
        return $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
    }
}
