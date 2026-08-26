<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $document['invoice_number'] }} - {{ $document['title'] }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: #111;
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            line-height: 1.25;
        }
        .controls {
            width: 210mm;
            margin: 12px auto;
            text-align: right;
            font-family: Arial, Helvetica, sans-serif;
        }
        .btn {
            display: inline-block;
            border: 1px solid #111827;
            border-radius: 4px;
            background: #111827;
            color: #fff;
            padding: 7px 11px;
            text-decoration: none;
            font-size: 12px;
        }
        .btn.secondary { background: #fff; color: #111827; }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 18px;
            background: #fff;
            padding: 22mm 18mm 16mm;
        }
        .document-title { margin-bottom: 8px; text-align: center; }
        .title-box {
            display: inline-block;
            min-width: 30mm;
            border: 2px solid #222;
            padding: 7px 22px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }
        .warning {
            margin-bottom: 8px;
            border: 1px solid #92400e;
            background: #fffbeb;
            padding: 7px 9px;
            color: #78350f;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .field-table { margin-bottom: 6px; }
        .field-table td { border: 1px solid #222; padding: 6px 8px; vertical-align: top; }
        .field-table .gap { width: 14px; border: 0; padding: 0; }
        .field-cell { min-height: 28px; }
        .party-cell { height: 46mm; }
        .label { font-weight: 700; }
        .party-line { min-height: 17px; }
        .party-address { min-height: 34px; }
        .party-phone { margin-top: 8px; }
        .purchaser-reference-fields { margin-top: 8px; }
        .additional-field { min-height: 18mm; }
        .additional-content { margin-top: 5px; }
        .invoice-lines { margin-top: 14px; }
        .invoice-lines th,
        .invoice-lines td { border: 1px solid #222; padding: 6px; vertical-align: top; }
        .invoice-lines th { height: 16mm; font-weight: 700; text-align: center; vertical-align: middle; }
        .invoice-lines tbody td { height: 9mm; }
        .summary-label { font-weight: 700; text-align: left; }
        .number { text-align: right; white-space: nowrap; }
        .muted { color: #444; font-size: 10px; }
        .footer-fields { margin-top: 14px; }
        .footer-fields td { border: 1px solid #222; min-height: 9mm; padding: 6px 8px; vertical-align: top; }
        @media print {
            body { background: #fff; }
            .controls { display: none !important; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
@php
    $isPdf = ($mode ?? 'print') === 'pdf';
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];
    $minimumPrintableLineRows = 5;
    $blankLineRows = max(0, $minimumPrintableLineRows - count($lines));
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $settlementRowsVisible = ! $zeroMoney($amounts['paid_total'])
        || ! $zeroMoney($amounts['credit_total'])
        || bccomp((string) $amounts['balance_due']['raw'], (string) $amounts['grand_total']['raw'], 6) !== 0;
@endphp

@if (! $isPdf)
    <div class="controls">
        <a href="#" id="printBtn" class="btn secondary">Print</a>
        @if (! empty($pdf_url))
            <a href="{{ $pdf_url }}" class="btn" target="_blank" rel="noopener">Download PDF</a>
        @endif
    </div>
@endif

<main class="sheet">
    <div class="document-title">
        <div class="title-box">{{ $document['title'] }}</div>
    </div>

    @foreach (($document['warnings'] ?? []) as $warning)
        <div class="warning">{{ $warning }}</div>
    @endforeach

    <table class="field-table">
        <tr>
            <td class="field-cell">
                <span class="label">Date of Invoice:</span>
                {{ $document['invoice_date'] ?? '' }}
            </td>
            <td class="gap"></td>
            <td class="field-cell">
                <span class="label">{{ $document['number_label'] }}</span>
                {{ $document['invoice_number'] }}
            </td>
        </tr>
    </table>

    <table class="field-table">
        <tr>
            @foreach (['supplier' => 'Supplier', 'purchaser' => 'Purchaser'] as $key => $label)
                @php($party = $document[$key])
                <td class="party-cell">
                    <div class="party-line"><span class="label">{{ $label }}'s TIN:</span> {{ $party['tin'] ?? '' }}</div>
                    @if (! empty($party['vat_registration_number']))
                        <div class="party-line"><span class="label">VAT Registration No:</span> {{ $party['vat_registration_number'] }}</div>
                    @endif
                    @if (! empty($party['svat_registration_number']))
                        <div class="party-line"><span class="label">SVAT Registration No:</span> {{ $party['svat_registration_number'] }}</div>
                    @endif
                    <div class="party-line"><span class="label">{{ $label }}'s Name:</span> {{ $party['name'] }}</div>
                    <div class="party-address"><span class="label">Address:</span> {{ $party['address'] ?? '' }}</div>
                    <div class="party-phone"><span class="label">Telephone No:</span> {{ $party['phone'] ?? '' }}</div>
                    @if ($key === 'purchaser' && ! empty($document['purchaser_reference_fields']))
                        <div class="purchaser-reference-fields">
                            @foreach ($document['purchaser_reference_fields'] as $field)
                                <div class="party-line"><span class="label">{{ $field['label'] }}:</span> {{ $field['value'] }}</div>
                            @endforeach
                        </div>
                    @endif
                </td>
                @if ($key === 'supplier')
                    <td class="gap"></td>
                @endif
            @endforeach
        </tr>
    </table>

    <table class="field-table">
        <tr>
            <td class="field-cell">
                <span class="label">Date of Delivery / Supply:</span>
                {{ $document['supply_date'] ?? '' }}
                @if (! empty($document['supply_period_start']) || ! empty($document['supply_period_end']))
                    <div class="muted">
                        Supply period: {{ $document['supply_period_start'] ?? '' }}
                        @if (! empty($document['supply_period_end'])) to {{ $document['supply_period_end'] }} @endif
                    </div>
                @endif
            </td>
            <td class="gap"></td>
            <td class="field-cell">
                <span class="label">Place of Supply:</span>
                {{ $document['place_of_supply'] ?? '' }}
            </td>
        </tr>
    </table>

    <table class="field-table">
        <tr>
            <td class="additional-field">
                <span class="label">Additional Information if any:</span>
                @if (! empty($document['due_date']) || ! empty($document['notes']))
                    <div class="additional-content">
                        @if (! empty($document['due_date']))<div>Due date: {{ $document['due_date'] }}</div>@endif
                        @if (! empty($document['notes']))<div>{{ $document['notes'] }}</div>@endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="invoice-lines">
        <thead>
        <tr>
            <th style="width: 13%;">Reference</th>
            <th>Description of Goods or Services</th>
            <th style="width: 12%;">Quantity</th>
            <th style="width: 14%;">Unit Price</th>
            <th style="width: 17%;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($lines as $line)
            <tr>
                <td>{{ $line['reference'] }}</td>
                <td>
                    {{ $line['description'] }}
                    @if (! empty($line['item']))<div class="muted">{{ $line['item'] }}</div>@endif
                </td>
                <td class="number">
                    {{ $line['quantity']['display'] }}
                    @if (! empty($line['uom'])) {{ $line['uom'] }} @endif
                </td>
                <td class="number">{{ $line['unit_price']['display'] }}</td>
                <td class="number">{{ $line['line_total']['display'] }}</td>
            </tr>
        @endforeach
        @for ($row = 0; $row < $blankLineRows; $row++)
            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
        @endfor

        <tr>
            <td colspan="4" class="summary-label">{{ $amounts['subtotal']['label'] }}:</td>
            <td class="number">{{ $amounts['subtotal']['display'] }}</td>
        </tr>
        @foreach (['discount_total', 'charge_total', 'adjustment_total'] as $amountKey)
            @if (! $zeroMoney($amounts[$amountKey]))
                <tr>
                    <td colspan="4" class="summary-label">{{ $amounts[$amountKey]['label'] }}:</td>
                    <td class="number">{{ $amounts[$amountKey]['display'] }}</td>
                </tr>
            @endif
        @endforeach
        <tr>
            <td colspan="4" class="summary-label">{{ $amounts['tax_total']['label'] }}:</td>
            <td class="number">{{ $amounts['tax_total']['display'] }}</td>
        </tr>
        <tr>
            <td colspan="4" class="summary-label">{{ $amounts['grand_total']['label'] }}:</td>
            <td class="number">{{ $amounts['grand_total']['display'] }}</td>
        </tr>
        @if ($settlementRowsVisible)
            <tr><td colspan="4" class="summary-label">{{ $amounts['paid_total']['label'] }}:</td><td class="number">{{ $amounts['paid_total']['display'] }}</td></tr>
            <tr><td colspan="4" class="summary-label">{{ $amounts['credit_total']['label'] }}:</td><td class="number">{{ $amounts['credit_total']['display'] }}</td></tr>
            <tr><td colspan="4" class="summary-label">{{ $amounts['balance_due']['label'] }}:</td><td class="number">{{ $amounts['balance_due']['display'] }}</td></tr>
        @endif
        </tbody>
    </table>

    <table class="footer-fields">
        <tr>
            <td><span class="label">Total Amount in words:</span> {{ $document['amount_in_words'] }}</td>
        </tr>
        <tr>
            <td>
                <span class="label">Mode of Payment:</span> {{ $document['payment_mode'] ?? '' }}
                @if (! empty($document['payment_terms']))
                    <div class="muted">Terms: {{ $document['payment_terms'] }}</div>
                @endif
            </td>
        </tr>
    </table>
</main>

@if (! $isPdf)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var button = document.getElementById('printBtn');
            if (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.print();
                });
            }
        });
    </script>
@endif
</body>
</html>
