<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $document['invoice_number'] }} - {{ $document['title'] }}</title>
    @php
        $isPdf = ($mode ?? 'print') === 'pdf';
        $isCompact = (bool) ($print_layout['is_compact'] ?? false);
        $isServiceA4 = (bool) ($print_layout['is_service_a4'] ?? false);
        $isServiceContinuous = (bool) ($print_layout['is_service_continuous'] ?? false);
        $usesFocusedPrint = (bool) ($document['uses_focused_print'] ?? false);
        $amounts = $document['amounts'];
        $lines = $document['lines'] ?? [];
        $minimumPrintableLineRows = $isCompact ? 1 : 5;
        $blankLineRows = max(0, $minimumPrintableLineRows - count($lines));
        $summaryColumnSpan = $usesFocusedPrint ? 3 : ($isCompact ? 5 : 4);
        $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
        $settlementRowsVisible = ! $zeroMoney($amounts['paid_total'])
            || ! $zeroMoney($amounts['credit_total'])
            || bccomp((string) $amounts['balance_due']['raw'], (string) $amounts['grand_total']['raw'], 6) !== 0;
    @endphp
    <style>
        @page { size: {{ $print_layout['css_page_size'] }}; margin: {{ $print_layout['css_page_margin'] }}; }
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
        .party-cell { width: 49%; height: 46mm; }
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
        .print-trace { margin-top: 7px; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 8px; text-align: right; }
        .compact-header { margin-bottom: 4px; }
        .compact-header td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }
        .compact-company { width: 58%; }
        .compact-company-name { font-size: 14px; font-weight: 700; }
        .compact-title { font-size: 13px; font-weight: 700; text-align: center; text-transform: uppercase; }
        .compact-meta { margin-top: 4px; }
        .signature-table { margin-top: 7px; }
        .signature-table td { width: 33.333%; padding: 9px 5px 2px; text-align: center; }
        .signature-line { border-top: 1px dotted #333; padding-top: 3px; }
        .pdf-output { background: #fff; }
        .pdf-output .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        .layout-a4 { font-size: 16px; line-height: 1.3; }
        .layout-a4 .title-box { font-size: 18px; }
        .layout-a4 .warning,
        .layout-a4 .muted { font-size: 12px; }
        .layout-a4 .print-trace { font-size: 10px; }
        .layout-a5,
        .layout-continuous { font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.2; }
        .layout-a5 .controls { width: 148mm; }
        .layout-continuous .controls { width: {{ $print_layout['continuous_width'] }}; }
        .layout-a5 .sheet { width: 148mm; min-height: 210mm; padding: 6mm; }
        .layout-continuous .sheet {
            width: {{ $print_layout['continuous_width'] }};
            min-height: {{ $print_layout['continuous_height'] }};
            padding: {{ $print_layout['continuous_vertical_margin'] }} {{ $print_layout['continuous_horizontal_margin'] }};
        }
        .layout-continuous .compact-company-name { font-size: 15px; }
        .layout-continuous .compact-title { font-size: 15px; }
        .layout-a5 .field-table,
        .layout-continuous .field-table { margin-bottom: 3px; }
        .layout-a5 .field-table td,
        .layout-continuous .field-table td { padding: 3px 5px; }
        .layout-a5 .field-table .gap,
        .layout-continuous .field-table .gap { width: 6px; }
        .layout-a5 .party-cell,
        .layout-continuous .party-cell { height: 22mm; }
        .layout-a5 .party-line,
        .layout-continuous .party-line { min-height: 10px; }
        .layout-a5 .party-address,
        .layout-continuous .party-address { min-height: 18px; }
        .layout-a5 .party-phone,
        .layout-a5 .purchaser-reference-fields,
        .layout-continuous .party-phone,
        .layout-continuous .purchaser-reference-fields { margin-top: 3px; }
        .layout-a5 .additional-field,
        .layout-continuous .additional-field { min-height: 6mm; }
        .layout-a5 .additional-content,
        .layout-continuous .additional-content { margin-top: 2px; }
        .layout-a5 .invoice-lines,
        .layout-continuous .invoice-lines { margin-top: 4px; }
        .layout-a5 .invoice-lines th,
        .layout-a5 .invoice-lines td,
        .layout-continuous .invoice-lines th,
        .layout-continuous .invoice-lines td { padding: 3px 4px; }
        .layout-a5 .invoice-lines th,
        .layout-continuous .invoice-lines th { height: 7mm; }
        .layout-a5 .invoice-lines tbody td,
        .layout-continuous .invoice-lines tbody td { height: 4.5mm; }
        .layout-a5 .muted,
        .layout-continuous .muted { font-size: 10px; }
        .layout-a5 .footer-fields,
        .layout-continuous .footer-fields { margin-top: 4px; }
        .layout-a5 .footer-fields td,
        .layout-continuous .footer-fields td { min-height: 5mm; padding: 3px 5px; }
        .layout-a5 .print-trace,
        .layout-continuous .print-trace { margin-top: 2px; font-size: 7px; white-space: nowrap; }
        .layout-a5 .signature-table,
        .layout-continuous .signature-table { margin-top: 16px; }
        .layout-a5 .signature-table td,
        .layout-continuous .signature-table td { padding-top: 5px; font-size: 10px; }
        .pdf-output.layout-a5 .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        .pdf-output.layout-continuous .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        .layout-service-a4 { color: #173956; font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.3; }
        .layout-service-a4 .sheet { padding: 12mm 11mm 9mm; }
        .layout-service-a4 .invoice-header { margin-bottom: 7mm; }
        .layout-service-a4 .invoice-header td { border: 0; vertical-align: top; }
        .layout-service-a4 .company-name { font-size: 19px; font-weight: 700; letter-spacing: .2px; }
        .layout-service-a4 .company-contact { margin-top: 3px; font-size: 9px; }
        .layout-service-a4 .invoice-heading { font-size: 19px; font-weight: 700; text-align: right; text-transform: uppercase; }
        .layout-service-a4 .identity-table,
        .layout-service-a4 .party-table,
        .layout-service-a4 .service-references { margin-bottom: 3mm; }
        .layout-service-a4 .identity-table td,
        .layout-service-a4 .party-table td,
        .layout-service-a4 .service-references td { border: 1px solid #8193a2; padding: 6px 8px; vertical-align: top; }
        .layout-service-a4 .identity-table td,
        .layout-service-a4 .party-table td { width: 50%; }
        .layout-service-a4 .party-table td { height: 36mm; }
        .layout-service-a4 .party-detail { margin-bottom: 5px; }
        .layout-service-a4 .party-detail strong { display: inline-block; min-width: 30%; }
        .layout-service-a4 .service-references { background: #f3f5f7; }
        .layout-service-a4 .service-references td { width: 25%; }
        .layout-service-a4 .reference-label { display: block; font-size: 9px; font-weight: 700; }
        .layout-service-a4 .service-lines { margin-top: 4mm; }
        .layout-service-a4 .service-lines thead { display: table-header-group; }
        .layout-service-a4 .service-lines tr { page-break-inside: avoid; }
        .layout-service-a4 .service-lines th,
        .layout-service-a4 .service-lines td { border: 1px solid #8193a2; padding: 6px 7px; vertical-align: middle; }
        .layout-service-a4 .service-lines th { background: #e9edf1; font-size: 9px; text-align: right; text-transform: uppercase; }
        .layout-service-a4 .service-lines th:first-child,
        .layout-service-a4 .service-lines th:nth-child(2) { text-align: left; }
        .layout-service-a4 .service-lines .line-number { text-align: center; }
        .layout-service-a4 .service-lines .item-code { display: block; color: #526a7e; font-size: 8px; }
        .layout-service-a4 .settlement-table { margin-top: 3mm; }
        .layout-service-a4 .settlement-table > tbody > tr > td { border: 1px solid #8193a2; padding: 0; vertical-align: top; }
        .layout-service-a4 .payment-box { width: 50%; padding: 9px 10px !important; }
        .layout-service-a4 .payment-box-title { margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .layout-service-a4 .payment-box div { margin-bottom: 6px; }
        .layout-service-a4 .totals-box { width: 50%; }
        .layout-service-a4 .totals-box td { border-bottom: 1px solid #8193a2; padding: 5px 7px; }
        .layout-service-a4 .totals-box tr:last-child td { border-bottom: 0; }
        .layout-service-a4 .totals-box td:last-child { width: 30%; text-align: right; white-space: nowrap; }
        .layout-service-a4 .totals-box .total-row { background: #e9edf1; font-weight: 700; }
        .layout-service-a4 .service-policy { margin-top: 12mm; text-align: center; line-height: 1.6; }
        .layout-service-a4 .signatures { margin-top: 14mm; }
        .layout-service-a4 .signatures td { width: 50%; padding: 0 8mm; text-align: center; }
        .layout-service-a4 .signature-rule { border-top: 1px solid #a9b6c1; padding-top: 5px; }
        .layout-service-a4 .service-footer { margin-top: 5mm; border-top: 1px solid #c4cdd5; color: #526a7e; font-size: 8px; }
        .layout-service-a4 .service-footer td { width: 50%; padding-top: 5px; vertical-align: top; }
        .layout-service-a4 .service-footer td:last-child { text-align: right; }
        .layout-service-a4 .print-trace { margin-top: 0; font-size: 8px; white-space: nowrap; }
        .pdf-output.layout-service-a4 .sheet { padding: 0; }
        .layout-service-continuous { color: #173956; font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.3; }
        .layout-service-continuous .sheet { padding: {{ $print_layout['css_page_margin'] }}; }
        .layout-service-continuous .controls { width: {{ $print_layout['continuous_width'] }}; }
        .layout-service-continuous .invoice-header { margin-bottom: 2mm; }
        .layout-service-continuous .invoice-header td { border: 0; vertical-align: top; }
        .layout-service-continuous .company-name { font-size: 16px; font-weight: 700; letter-spacing: .2px; }
        .layout-service-continuous .company-contact { margin-top: 3px; font-size: 8px; }
        .layout-service-continuous .invoice-heading { font-size: 16px; font-weight: 700; text-align: right; text-transform: uppercase; }
        .layout-service-continuous .identity-table,
        .layout-service-continuous .party-table { margin-bottom: 2mm; }
        .layout-service-continuous .identity-table td,
        .layout-service-continuous .party-table td { width: 50%; border: 1px solid #8193a2; padding: 5px 7px; vertical-align: top; }
        .layout-service-continuous .party-table td { height: 20mm; }
        .layout-service-continuous .party-detail { margin-bottom: 3px; }
        .layout-service-continuous .party-detail strong { display: inline-block; min-width: 28%; }
        .layout-service-continuous .service-inline-references { margin-top: 3px; }
        .layout-service-continuous .service-inline-references span { display: inline-block; margin-right: 6mm; }
        .layout-service-continuous .service-lines { margin-top: 2mm; }
        .layout-service-continuous .service-lines thead { display: table-header-group; }
        .layout-service-continuous .service-lines tr { page-break-inside: avoid; }
        .layout-service-continuous .service-lines th,
        .layout-service-continuous .service-lines td { border: 1px solid #8193a2; padding: 4px 5px; vertical-align: middle; }
        .layout-service-continuous .service-lines th { background: #e9edf1; font-size: 8px; text-align: right; text-transform: uppercase; }
        .layout-service-continuous .service-lines th:first-child,
        .layout-service-continuous .service-lines th:nth-child(2) { text-align: left; }
        .layout-service-continuous .service-lines .line-number { text-align: center; }
        .layout-service-continuous .service-lines .item-code { display: block; color: #526a7e; font-size: 7px; }
        .layout-service-continuous .settlement-table { margin-top: 2mm; }
        .layout-service-continuous .settlement-table > tbody > tr > td { border: 1px solid #8193a2; padding: 0; vertical-align: top; }
        .layout-service-continuous .payment-box { width: 50%; padding: 7px 9px !important; }
        .layout-service-continuous .payment-box-title { margin-bottom: 7px; font-weight: 700; text-transform: uppercase; }
        .layout-service-continuous .payment-box div { margin-bottom: 5px; }
        .layout-service-continuous .totals-box { width: 50%; }
        .layout-service-continuous .totals-box td { border-bottom: 1px solid #8193a2; padding: 3px 5px; }
        .layout-service-continuous .totals-box tr:last-child td { border-bottom: 0; }
        .layout-service-continuous .totals-box td:last-child { width: 28%; text-align: right; white-space: nowrap; }
        .layout-service-continuous .totals-box .total-row { background: #e9edf1; font-weight: 700; }
        .layout-service-continuous .service-policy { margin-top: 2mm; text-align: center; line-height: 1.4; }
        .layout-service-continuous .service-content { min-height: {{ $print_layout['service_content_min_height'] }}; }
        .layout-service-continuous .signatures { margin-top: 3mm; }
        .layout-service-continuous .signatures td { width: 50%; padding: 0 8mm; text-align: center; }
        .layout-service-continuous .signature-rule { border-top: 1px solid #a9b6c1; padding-top: 3px; }
        .layout-service-continuous .service-footer { margin-top: 2mm; border-top: 1px solid #c4cdd5; color: #526a7e; font-size: 8px; }
        .layout-service-continuous .service-footer td { width: 35%; padding: 8px 0 3px; vertical-align: top; }
        .layout-service-continuous .service-footer td:last-child { width: 65%; text-align: right; }
        .layout-service-continuous .print-trace { margin-top: 0; font-size: 8px; white-space: normal; overflow-wrap: break-word; }
        .pdf-output.layout-service-continuous .sheet { padding: 0; }
        @media print {
            body { background: #fff; }
            .controls { display: none !important; }
            .sheet,
            .layout-a5 .sheet,
            .layout-continuous .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="{{ $print_layout['css_class'] }}{{ $isServiceA4 ? ' layout-service-a4' : '' }}{{ $isServiceContinuous ? ' layout-service-continuous' : '' }}{{ $isPdf ? ' pdf-output' : '' }}">

@if (! $isPdf)
    <div class="controls">
        <a href="#" id="printBtn" class="btn secondary">Print</a>
        @if (! empty($pdf_url))
            <a href="{{ $pdf_url }}" class="btn" target="_blank" rel="noopener">Download PDF</a>
        @endif
    </div>
@endif

<main class="sheet">
    @if ($isServiceA4 || $isServiceContinuous)
        @include('invoice.partials.service')
    @else
    @if ($isCompact)
        <table class="compact-header">
            <tr>
                <td class="compact-company">
                    <div class="compact-company-name">{{ $document['capitalizes_company_header'] ? mb_strtoupper($document['supplier']['name']) : $document['supplier']['name'] }}</div>
                    @if (! empty($document['supplier']['address']))<div>{{ $document['supplier']['address'] }}</div>@endif
                    <div>
                        @if (! empty($document['supplier']['phone']))Tel: {{ $document['supplier']['phone'] }}@endif
                        @if (! empty($document['supplier']['vat_registration_number'])) | VAT: {{ $document['supplier']['vat_registration_number'] }}@endif
                    </div>
                </td>
                <td>
                    <div class="compact-title">{{ $document['title'] }}</div>
                    <div class="compact-meta"><span class="label">{{ $document['number_label'] }}</span> {{ $document['invoice_number'] }}</div>
                    <div><span class="label">Date:</span> {{ $document['invoice_date'] ?? '' }}</div>
                </td>
            </tr>
        </table>
    @else
        <div class="document-title">
            <div class="title-box">{{ $document['title'] }}</div>
        </div>
    @endif

    @foreach (($document['warnings'] ?? []) as $warning)
        <div class="warning">{{ $warning }}</div>
    @endforeach

    @unless ($isCompact)
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
    @endunless

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
            @if ($usesFocusedPrint)
                <th>Item Name</th>
            @else
                <th style="width: 13%;">Reference</th>
                <th>Description of Goods or Services</th>
            @endif
            <th style="width: 12%;">Quantity</th>
            <th style="width: 14%;">Unit Price</th>
            @if ($isCompact && ! $usesFocusedPrint)<th style="width: 12%;">Discount</th>@endif
            <th style="width: 17%;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($lines as $line)
            <tr>
                @if ($usesFocusedPrint)
                    <td>{{ $line['display_name'] }}</td>
                @else
                    <td>{{ $line['reference'] }}</td>
                    <td>
                        {{ $line['description'] }}
                        @if (! empty($line['item']))<div class="muted">{{ $line['item'] }}</div>@endif
                    </td>
                @endif
                <td class="number">
                    {{ $line['quantity']['display'] }}
                </td>
                <td class="number">{{ $line['unit_price']['display'] }}</td>
                @if ($isCompact && ! $usesFocusedPrint)<td class="number">{{ $line['discount_amount']['display'] }}</td>@endif
                <td class="number">{{ $line['line_total']['display'] }}</td>
            </tr>
        @endforeach
        @for ($row = 0; $row < $blankLineRows; $row++)
            <tr><td>&nbsp;</td>@unless ($usesFocusedPrint)<td></td>@endunless<td></td><td></td>@if ($isCompact && ! $usesFocusedPrint)<td></td>@endif<td></td></tr>
        @endfor

        @if ($document['shows_service_discount_breakdown'])
            @foreach (['line_discount_total', 'bill_discount_total'] as $amountKey)
                <tr>
                    <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts[$amountKey]['label'] }}:</td>
                    <td class="number">@unless ($zeroMoney($amounts[$amountKey]))-@endunless{{ $amounts[$amountKey]['display'] }}</td>
                </tr>
            @endforeach
        @endif
        @unless ($usesFocusedPrint)
            <tr>
                <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['subtotal']['label'] }}:</td>
                <td class="number">{{ $amounts['subtotal']['display'] }}</td>
            </tr>
            @foreach (['discount_total', 'charge_total', 'adjustment_total'] as $amountKey)
                @if (! $zeroMoney($amounts[$amountKey]))
                    <tr>
                        <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts[$amountKey]['label'] }}:</td>
                        <td class="number">{{ $amounts[$amountKey]['display'] }}</td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['tax_total']['label'] }}:</td>
                <td class="number">{{ $amounts['tax_total']['display'] }}</td>
            </tr>
        @endunless
        <tr>
            <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['grand_total']['label'] }}:</td>
            <td class="number">{{ $amounts['grand_total']['display'] }}</td>
        </tr>
        @if ($settlementRowsVisible || $usesFocusedPrint)
            @if (! $zeroMoney($amounts['paid_total']))
                <tr><td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['paid_total']['label'] }}:</td><td class="number">{{ $amounts['paid_total']['display'] }}</td></tr>
            @endif
            <tr><td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['balance_due']['label'] }}:</td><td class="number">{{ $amounts['balance_due']['display'] }}</td></tr>
        @endif
        </tbody>
    </table>

    <table class="footer-fields">
        <tr>
            <td>
                <span class="label">Mode of Payment:</span> {{ $document['resolved_payment_mode'] ?? '' }}
                @if (! empty($document['payment_terms']))
                    <div class="muted">Terms: {{ $document['payment_terms'] }}</div>
                @endif
            </td>
        </tr>
    </table>
    @if ($isCompact)
        <table class="signature-table">
            <tr>
                <td><div class="signature-line">Prepared by</div></td>
                <td><div class="signature-line">Authorized by</div></td>
                <td><div class="signature-line">Customer signature</div></td>
            </tr>
        </table>
    @endif
    @endif
    @if ($isServiceA4 || $isServiceContinuous)
        <table class="service-footer">
            <tr>
                <td>Thank you for your business.</td>
                <td class="print-trace">
                    @if (! empty($print_context))
                        Printed: {{ $print_context['printed_at'] }} | By: {{ $print_context['printed_by'] }}@if (! empty($print_context['copy_type'])) | {{ $print_context['copy_type'] }}@endif
                    @endif
                </td>
            </tr>
        </table>
    @elseif (! empty($print_context))
        <div class="print-trace">
            Printed: {{ $print_context['printed_at'] }} | By: {{ $print_context['printed_by'] }}@if (! empty($print_context['copy_type'])) | {{ $print_context['copy_type'] }}@endif
        </div>
    @endif
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
