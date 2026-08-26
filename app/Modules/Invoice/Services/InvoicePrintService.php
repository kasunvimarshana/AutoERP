<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceDocumentKind;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceDocumentSnapshot;
use Modules\Invoice\Models\InvoiceLine;

final class InvoicePrintService
{
    private const DEFAULT_TAX_LABEL = 'Tax';

    public const SIGNED_URL_TTL_MINUTES = 15;

    public const PDF_PAPER_SIZE = 'A4';

    public const PDF_ORIENTATION = 'portrait';

    private const MONEY_SCALE = 2;

    private const QUANTITY_SCALE = 3;

    public function __construct(private readonly InvoiceAmountInWordsFormatter $amountInWords) {}

    /** @return Builder<Invoice> */
    public function scopedQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = Invoice::query()
            ->with([
                'tenant',
                'organizationUnit',
                'documentSnapshot',
                'lines' => static fn ($query) => $query->orderBy('line_number'),
            ])
            ->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function findScoped(int $invoiceId, int $tenantId, ?int $organizationUnitId): ?Invoice
    {
        return $this->scopedQuery($tenantId, $organizationUnitId)->find($invoiceId);
    }

    /** @return array<string, mixed> */
    public function viewData(Invoice $invoice, ?string $pdfUrl = null, string $mode = 'print'): array
    {
        $invoice->loadMissing([
            'tenant',
            'organizationUnit',
            'documentSnapshot',
            'lines' => static fn ($query) => $query->orderBy('line_number'),
        ]);

        $currency = $this->currency($invoice);
        $snapshot = $invoice->documentSnapshot;
        $kind = $snapshot instanceof InvoiceDocumentSnapshot
            ? $this->documentKind($snapshot->document_kind)
            : $this->legacyDocumentKind($invoice);
        $supplier = $snapshot instanceof InvoiceDocumentSnapshot
            ? $this->snapshotParty($snapshot, 'seller')
            : $this->supplier($invoice);
        $purchaser = $snapshot instanceof InvoiceDocumentSnapshot
            ? $this->snapshotParty($snapshot, 'buyer')
            : $this->purchaser($invoice);
        $taxLabel = $this->taxLabel($invoice->lines);

        return [
            'mode' => $mode,
            'pdf_url' => $pdfUrl,
            'document' => [
                'title' => $kind->title(),
                'number_label' => $kind->numberLabel(),
                'invoice_number' => $this->nullableString($invoice->invoice_number) ?? 'Unnumbered invoice',
                'invoice_date' => $this->dateString($invoice->invoice_date),
                'due_date' => $this->dateString($invoice->due_date),
                'invoice_type' => $this->label($this->enumValue($invoice->invoice_type)),
                'direction' => $this->label($this->enumValue($invoice->direction)),
                'status' => $this->label($this->enumValue($invoice->status)),
                'tax_label' => $taxLabel,
                'currency' => $currency,
                'supplier' => $supplier,
                'purchaser' => $purchaser,
                'purchaser_reference_fields' => $snapshot instanceof InvoiceDocumentSnapshot
                    ? ($snapshot->purchaser_reference_fields ?? [])
                    : [],
                'supply_date' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->dateString($snapshot->supply_date) : null,
                'supply_period_start' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->dateString($snapshot->supply_period_start) : null,
                'supply_period_end' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->dateString($snapshot->supply_period_end) : null,
                'place_of_supply' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->nullableString($snapshot->place_of_supply) : null,
                'payment_mode' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->nullableString($snapshot->payment_mode) : null,
                'payment_terms' => $snapshot instanceof InvoiceDocumentSnapshot ? $this->nullableString($snapshot->payment_terms) : null,
                'amount_in_words' => $this->amountInWords->format($invoice->grand_total, $currency['code']),
                'notes' => $this->nullableString($invoice->notes),
                'warnings' => [],
                'lines' => $this->lines($invoice->lines, $currency),
                'amounts' => $this->amounts($invoice, $currency, $taxLabel),
            ],
        ];
    }

    public function filename(Invoice $invoice): string
    {
        $number = $this->nullableString($invoice->invoice_number) ?? (string) $invoice->getKey();
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        $safeNumber = trim((string) $safeNumber, '-');

        return 'invoice-'.($safeNumber === '' ? (string) $invoice->getKey() : $safeNumber).'.pdf';
    }

    /** @param Collection<int, InvoiceLine> $lines @return list<array<string, mixed>> */
    private function lines(Collection $lines, array $currency): array
    {
        return $lines
            ->sortBy('line_number')
            ->values()
            ->map(fn (InvoiceLine $line): array => [
                'line_number' => (int) $line->line_number,
                'reference' => $this->lineReference($line),
                'item' => $this->lineItemLabel($line),
                'description' => (string) $line->description,
                'quantity' => [
                    'raw' => (string) $line->quantity,
                    'display' => $this->formatDecimal($line->quantity, self::QUANTITY_SCALE),
                ],
                'uom' => $this->nullableString($line->uom_code_snapshot)
                    ?? $this->nullableString($line->uom_name_snapshot),
                'unit_price' => $this->money($line->unit_price, $currency),
                'discount_amount' => $this->money($line->discount_amount, $currency),
                'tax_amount' => $this->money($line->tax_amount, $currency),
                'charge_amount' => $this->money($line->charge_amount, $currency),
                'line_total' => $this->money($line->line_total, $currency),
            ])
            ->all();
    }

    /** @return array<string, array{label:string, raw:string, display:string}> */
    private function amounts(Invoice $invoice, array $currency, string $taxLabel): array
    {
        return [
            'subtotal' => $this->labeledMoney('Total Value of Supply', $invoice->subtotal, $currency),
            'discount_total' => $this->labeledMoney('Discounts', $invoice->discount_total, $currency),
            'tax_total' => $this->labeledMoney($taxLabel.' Amount', $invoice->tax_total, $currency),
            'charge_total' => $this->labeledMoney('Charges', $invoice->charge_total, $currency),
            'adjustment_total' => $this->labeledMoney('Other adjustments', $invoice->adjustment_total, $currency),
            'grand_total' => $this->labeledMoney('Total Amount including '.$taxLabel, $invoice->grand_total, $currency),
            'paid_total' => $this->labeledMoney('Paid', $invoice->paid_total, $currency),
            'credit_total' => $this->labeledMoney('Credits', $invoice->credit_total, $currency),
            'balance_due' => $this->labeledMoney('Balance due', $invoice->balance_due, $currency),
        ];
    }

    /** @param Collection<int, InvoiceLine> $lines */
    private function taxLabel(Collection $lines): string
    {
        $taxNames = [];
        foreach ($lines as $line) {
            if (! is_array($line->tax_snapshot)) {
                continue;
            }

            foreach ($line->tax_snapshot as $tax) {
                if (! is_array($tax) || (bool) ($tax['is_withholding'] ?? false)) {
                    continue;
                }

                $name = $this->nullableString($tax['tax_code'] ?? null)
                    ?? $this->nullableString($tax['tax_name'] ?? null)
                    ?? $this->nullableString($tax['tax_type'] ?? null);
                if ($name !== null) {
                    $taxNames[] = mb_strtolower($name);
                }
            }
        }

        if ($taxNames !== [] && collect($taxNames)->every(static fn (string $name): bool => str_contains($name, 'vat'))) {
            return 'VAT';
        }

        return self::DEFAULT_TAX_LABEL;
    }

    /** @return array{label:string, raw:string, display:string} */
    private function labeledMoney(string $label, mixed $value, array $currency): array
    {
        return [
            'label' => $label,
            ...$this->money($value, $currency),
        ];
    }

    /** @return array{raw:string, display:string} */
    private function money(mixed $value, array $currency): array
    {
        $raw = $this->decimalString($value);

        return [
            'raw' => $raw,
            'display' => trim(($currency['prefix'] ?? '').' '.$this->formatDecimal($raw, self::MONEY_SCALE)),
        ];
    }

    /** @return array{code:?string, symbol:?string, prefix:string} */
    private function currency(Invoice $invoice): array
    {
        $symbol = $this->nullableString($invoice->currency_symbol_snapshot);
        $code = $this->nullableString($invoice->currency_code_snapshot);

        return [
            'code' => $code,
            'symbol' => $symbol,
            'prefix' => $symbol ?? $code ?? '',
        ];
    }

    /** @return array{name:string,tin:?string,vat_registration_number:?string,svat_registration_number:?string,address:?string,phone:?string,email:?string} */
    private function snapshotParty(InvoiceDocumentSnapshot $snapshot, string $prefix): array
    {
        return [
            'name' => (string) $snapshot->getAttribute($prefix.'_legal_name'),
            'tin' => $this->nullableString($snapshot->getAttribute($prefix.'_tin')),
            'vat_registration_number' => $this->nullableString($snapshot->getAttribute($prefix.'_vat_registration_number')),
            'svat_registration_number' => $this->nullableString($snapshot->getAttribute($prefix.'_svat_registration_number')),
            'address' => $this->nullableString($snapshot->getAttribute($prefix.'_address')),
            'phone' => $this->nullableString($snapshot->getAttribute($prefix.'_phone')),
            'email' => $this->nullableString($snapshot->getAttribute($prefix.'_email')),
        ];
    }

    private function supplier(Invoice $invoice): array
    {
        return $this->isOutbound($invoice)
            ? $this->organizationParty($invoice)
            : $this->counterparty($invoice, 'Supplier');
    }

    private function purchaser(Invoice $invoice): array
    {
        return $this->isOutbound($invoice)
            ? $this->counterparty($invoice, 'Purchaser')
            : $this->organizationParty($invoice);
    }

    private function organizationParty(Invoice $invoice): array
    {
        $organization = $invoice->organizationUnit;
        $tenant = $invoice->tenant;
        $name = $this->nullableString($organization?->name)
            ?? $this->nullableString($tenant?->name)
            ?? 'Organization';

        return [
            'name' => $name,
            'tin' => null,
            'vat_registration_number' => null,
            'svat_registration_number' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
        ];
    }

    private function counterparty(Invoice $invoice, string $fallbackName): array
    {
        $name = $this->nullableString($invoice->party_legal_name_snapshot)
            ?? $this->nullableString($invoice->party_name_snapshot)
            ?? $this->nullableString($invoice->party_code_snapshot)
            ?? $fallbackName;

        return [
            'name' => $name,
            'tin' => $this->nullableString($invoice->party_tax_registration_snapshot),
            'vat_registration_number' => null,
            'svat_registration_number' => null,
            'address' => null,
            'phone' => $this->nullableString($invoice->party_phone_snapshot),
            'email' => $this->nullableString($invoice->party_email_snapshot),
        ];
    }

    private function documentKind(mixed $value): InvoiceDocumentKind
    {
        return $value instanceof InvoiceDocumentKind
            ? $value
            : InvoiceDocumentKind::from((string) $value);
    }

    private function legacyDocumentKind(Invoice $invoice): InvoiceDocumentKind
    {
        $type = $this->enumValue($invoice->invoice_type);
        $direction = $this->enumValue($invoice->direction);
        if ($type === InvoiceType::Credit->value) {
            return InvoiceDocumentKind::CreditNote;
        }
        if ($type === InvoiceType::Debit->value) {
            return InvoiceDocumentKind::DebitNote;
        }
        if ($type === InvoiceType::Rental->value && $direction === InvoiceDirection::Inbound->value) {
            return InvoiceDocumentKind::OwnerPayableVoucher;
        }
        if ($direction === InvoiceDirection::Inbound->value) {
            return InvoiceDocumentKind::PurchaseInvoice;
        }

        return $invoice->lines->contains(static function (InvoiceLine $line): bool {
            if (! is_array($line->tax_snapshot)) {
                return false;
            }
            foreach ($line->tax_snapshot as $tax) {
                if (is_array($tax) && ! (bool) ($tax['is_withholding'] ?? false)) {
                    return true;
                }
            }

            return false;
        }) ? InvoiceDocumentKind::TaxInvoice : InvoiceDocumentKind::Invoice;
    }

    private function lineItemLabel(InvoiceLine $line): ?string
    {
        $name = $this->nullableString($line->item_name_snapshot);
        $code = $this->nullableString($line->item_code_snapshot);

        return $name !== null && $code !== null ? $code.' - '.$name : $name ?? $code;
    }

    private function lineReference(InvoiceLine $line): string
    {
        return $this->nullableString($line->item_code_snapshot)
            ?? $this->nullableString($line->item_name_snapshot)
            ?? (string) $line->line_number;
    }

    private function isOutbound(Invoice $invoice): bool
    {
        return $this->enumValue($invoice->direction) === InvoiceDirection::Outbound->value;
    }

    private function enumValue(mixed $value): ?string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $this->nullableString($value);
    }

    private function label(?string $value): string
    {
        $value = $this->nullableString($value);

        return $value === null ? 'Not set' : ucwords(str_replace('_', ' ', $value));
    }

    private function dateString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $this->nullableString($value);
    }

    private function decimalString(mixed $value): string
    {
        $value = $this->nullableString($value);

        return $value === null ? '0.000000' : $value;
    }

    private function formatDecimal(mixed $value, int $scale): string
    {
        $decimal = $this->decimalString($value);
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $decimal) !== 1) {
            return number_format((float) $decimal, $scale);
        }

        $increment = '0.'.str_repeat('0', $scale).'5';
        $rounded = bcadd($decimal, str_starts_with($decimal, '-') ? '-'.$increment : $increment, $scale);
        if (bccomp($rounded, '0', $scale) === 0) {
            $rounded = '0'.($scale > 0 ? '.'.str_repeat('0', $scale) : '');
        }

        $negative = str_starts_with($rounded, '-');
        $absolute = ltrim($rounded, '-');
        [$whole, $fraction] = array_pad(explode('.', $absolute, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return ($negative ? '-' : '').$whole.($scale > 0 ? '.'.str_pad($fraction, $scale, '0') : '');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
