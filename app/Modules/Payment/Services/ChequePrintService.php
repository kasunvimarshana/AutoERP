<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\ChequePrintStatus;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Http\Resources\ChequeTemplateResource;
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
        $dateFormat = (string) ($template->metadata['date_format'] ?? 'd/m/Y');
        $formattedDate = $chequeDate instanceof CarbonInterface ? $chequeDate->format($dateFormat) : (string) $chequeDate;
        $amount = (string) $line->amount;
        $metadata = is_array($line->metadata) ? $line->metadata : [];
        $payeeName = (string) ($metadata['payee_name'] ?? $payment->payee_name);

        $tMeta = is_array($template->metadata) ? $template->metadata : [];
        $prefix = (string) ($tMeta['amount_prefix'] ?? '**');
        $suffix = (string) ($tMeta['amount_suffix'] ?? '**');
        $formattedAmount = $prefix.number_format((float) $amount, 2).$suffix;

        $amountWords = $this->amountInWords->convert($amount);
        $wordsLine1 = $amountWords;
        $wordsLine2 = '';
        if (mb_strlen($amountWords) > 45) {
            $wrapped = wordwrap($amountWords, 45, "\n");
            $lines = explode("\n", $wrapped, 2);
            $wordsLine1 = $lines[0];
            $wordsLine2 = $lines[1] ?? '';
        }

        $rawDigits = preg_replace('/\D/', '', $formattedDate) ?? '';
        $dateDigits = str_split($rawDigits);

        $crossingType = (string) ($tMeta['crossing_type'] ?? 'ac_payee');
        $crossingText = match ($crossingType) {
            'ac_payee' => 'A/C PAYEE ONLY',
            'not_negotiable' => 'NOT NEGOTIABLE',
            default => null,
        };

        return [
            'payment' => [
                'id' => (int) $payment->getKey(),
                'payment_number' => (string) $payment->payment_number,
                'document_status' => $this->documentStatus($payment)->value,
                'payment_type' => $payment->payment_type instanceof \BackedEnum ? $payment->payment_type->value : (string) $payment->payment_type,
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
                'formatted_amount' => $formattedAmount,
                'amount_prefix' => $prefix,
                'amount_suffix' => $suffix,
                'amount_in_words' => $amountWords,
                'amount_words_line_1' => $wordsLine1,
                'amount_words_line_2' => $wordsLine2,
                'cheque_number' => $line->instrument_number ?? $payment->cheque_number,
                'cheque_date' => $chequeDate instanceof CarbonInterface ? $chequeDate->toDateString() : $chequeDate,
                'formatted_cheque_date' => $formattedDate,
                'date_digits' => $dateDigits,
                'currency_code' => (string) ($tMeta['currency_code'] ?? 'LKR'),
                'crossing_type' => $crossingType,
                'crossing_text' => $crossingText,
                'show_bearer_strike' => (bool) ($tMeta['bearer_strike_through'] ?? true),
                'memo_text' => (string) $payment->payment_number,
                'external_bank_name' => $line->external_bank_name,
                'external_bank_branch' => $line->external_bank_branch,
            ],
            'template' => array_merge($template->toArray(), (new ChequeTemplateResource($template))->resolve()),
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
