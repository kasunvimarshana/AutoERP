<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $document['number'] }}</title>
    <style>
        @page { margin: 18mm 13mm 19mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; }
        .watermark { position: fixed; top: 38%; left: 16%; z-index: -1; color: #e2e8f0; font-size: 76px; font-weight: 700; letter-spacing: 10px; transform: rotate(-28deg); }
        .top { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .top td { vertical-align: top; }
        .brand-name { color: #0f3f70; font-size: 19px; font-weight: 700; margin-bottom: 4px; }
        .document-title { color: #0f3f70; font-size: 24px; font-weight: 700; letter-spacing: 1px; text-align: right; }
        .document-number { margin-top: 3px; font-size: 11px; font-weight: 700; text-align: right; }
        .status { display: inline-block; margin-top: 7px; padding: 3px 8px; border: 1px solid #94a3b8; border-radius: 10px; color: #475569; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .party-grid { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin: 0 -7px 14px; }
        .party-grid td { width: 50%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; vertical-align: top; }
        .section-label { color: #0f3f70; font-size: 8px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; margin-bottom: 6px; }
        .party-name { font-size: 11px; font-weight: 700; margin-bottom: 3px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
        .meta-label { color: #64748b; display: block; font-size: 7px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        .meta-value { display: block; font-weight: 700; margin-top: 2px; }
        .lines { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .lines thead { display: table-header-group; }
        .lines tr { page-break-inside: avoid; }
        .lines th { background: #0f3f70; color: #fff; padding: 7px 5px; font-size: 7px; letter-spacing: .3px; text-transform: uppercase; }
        .lines td { border-bottom: 1px solid #dbe3ec; padding: 7px 5px; vertical-align: top; }
        .lines .num { text-align: right; white-space: nowrap; }
        .item-name { font-weight: 700; }
        .item-meta { color: #64748b; font-size: 8px; margin-top: 2px; }
        .summary-wrap { width: 100%; margin-top: 10px; page-break-inside: avoid; }
        .summary-spacer { width: 53%; }
        .summary-cell { width: 47%; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 6px; }
        .summary .value { text-align: right; white-space: nowrap; }
        .summary .grand td { border-top: 2px solid #0f3f70; color: #0f3f70; font-size: 11px; font-weight: 700; padding-top: 7px; white-space: nowrap; }
        .notes { margin-top: 14px; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 4px; page-break-inside: avoid; }
        .approval { margin-top: 24px; width: 100%; page-break-inside: avoid; }
        .approval td { width: 50%; vertical-align: bottom; }
        .signature { width: 180px; padding-top: 22px; border-top: 1px solid #64748b; color: #475569; }
        .footer { position: fixed; right: 0; bottom: -11mm; left: 0; border-top: 1px solid #dbe3ec; padding-top: 5px; color: #94a3b8; font-size: 7px; text-align: center; }
    </style>
</head>
<body>
@if ($document['is_draft'])
    <div class="watermark">DRAFT</div>
@endif

<table class="top">
    <tr>
        <td>
            <div class="brand-name">{{ $document['organization']['name'] }}</div>
            @if ($document['organization']['address'])<div>{{ $document['organization']['address'] }}</div>@endif
            @if ($document['organization']['phone'])<div>Tel: {{ $document['organization']['phone'] }}</div>@endif
            @if ($document['organization']['email'])<div>{{ $document['organization']['email'] }}</div>@endif
            @if ($document['organization']['tin'])<div>TIN: {{ $document['organization']['tin'] }}</div>@endif
            @if ($document['organization']['vat'])<div>VAT: {{ $document['organization']['vat'] }}</div>@endif
        </td>
        <td class="right">
            <div class="document-title">PURCHASE ORDER</div>
            <div class="document-number">{{ $document['number'] }}</div>
            <span class="status">{{ $document['status_label'] }}</span>
        </td>
    </tr>
</table>

<table class="party-grid">
    <tr>
        <td>
            <div class="section-label">Supplier</div>
            <div class="party-name">{{ $document['supplier']['name'] }}</div>
            @if ($document['supplier']['code'])<div>Code: {{ $document['supplier']['code'] }}</div>@endif
            @if ($document['supplier']['address'])<div>{{ $document['supplier']['address'] }}</div>@endif
            @if ($document['supplier']['phone'])<div>Tel: {{ $document['supplier']['phone'] }}</div>@endif
            @if ($document['supplier']['email'])<div>{{ $document['supplier']['email'] }}</div>@endif
            @if ($document['supplier']['tin'])<div>TIN: {{ $document['supplier']['tin'] }}</div>@endif
            @if ($document['supplier']['vat'])<div>VAT: {{ $document['supplier']['vat'] }}</div>@endif
        </td>
        <td>
            <div class="section-label">Deliver To</div>
            <div class="party-name">{{ $document['organization']['name'] }}</div>
            @if ($document['delivery']['warehouse'])<div>{{ $document['delivery']['warehouse'] }}</div>@endif
            @if ($document['delivery']['location'])<div>{{ $document['delivery']['location'] }}</div>@endif
            @if ($document['organization']['address'])<div>{{ $document['organization']['address'] }}</div>@endif
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td><span class="meta-label">PO Date</span><span class="meta-value">{{ $document['order_date'] ?? '-' }}</span></td>
        <td><span class="meta-label">Expected Delivery</span><span class="meta-value">{{ $document['expected_delivery_date'] ?? '-' }}</span></td>
        <td><span class="meta-label">Currency</span><span class="meta-value">{{ $document['currency']['code'] ?? '-' }}</span></td>
        <td><span class="meta-label">Exchange Rate</span><span class="meta-value">{{ $document['exchange_rate'] }}</span></td>
    </tr>
</table>

<table class="lines">
    <thead>
        <tr>
            <th style="width: 5%">#</th>
            <th style="width: 35%; text-align: left">Item / Description</th>
            <th style="width: 10%; text-align: right">Quantity</th>
            <th style="width: 8%">UOM</th>
            <th style="width: 12%; text-align: right">Unit Price</th>
            <th style="width: 10%; text-align: right">Discount</th>
            <th style="width: 9%; text-align: right">Tax</th>
            <th style="width: 11%; text-align: right">Total</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($document['lines'] as $line)
        <tr>
            <td>{{ $line['line_number'] }}</td>
            <td>
                <div class="item-name">{{ $line['description'] }}</div>
                @if ($line['reference'] || $line['variant'])
                    <div class="item-meta">{{ implode(' | ', array_filter([$line['reference'], $line['variant']])) }}</div>
                @endif
            </td>
            <td class="num">{{ $line['quantity'] }}</td>
            <td style="text-align: center">{{ $line['uom'] ?? '-' }}</td>
            <td class="num">{{ $line['unit_price']['amount'] }}</td>
            <td class="num">{{ $line['discount']['amount'] }}</td>
            <td class="num">{{ $line['tax']['amount'] }}</td>
            <td class="num"><strong>{{ $line['total']['amount'] }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="summary-wrap"><tr><td class="summary-spacer"></td><td class="summary-cell">
    <table class="summary">
        <tr><td>Subtotal</td><td class="value">{{ $document['amounts']['subtotal']['display'] }}</td></tr>
        @if ((float) $document['amounts']['discount_total']['raw'] !== 0.0)<tr><td>Discounts</td><td class="value">-{{ $document['amounts']['discount_total']['display'] }}</td></tr>@endif
        @if ((float) $document['amounts']['tax_total']['raw'] !== 0.0)<tr><td>Tax</td><td class="value">{{ $document['amounts']['tax_total']['display'] }}</td></tr>@endif
        @if ((float) $document['amounts']['charge_total']['raw'] !== 0.0)<tr><td>Charges</td><td class="value">{{ $document['amounts']['charge_total']['display'] }}</td></tr>@endif
        @if ((float) $document['amounts']['adjustment_total']['raw'] !== 0.0)<tr><td>Header adjustments</td><td class="value">{{ $document['amounts']['adjustment_total']['display'] }}</td></tr>@endif
        <tr class="grand"><td>Grand Total</td><td class="value">{{ $document['amounts']['grand_total']['display'] }}</td></tr>
    </table>
</td></tr></table>

@if ($document['notes'])
    <div class="notes"><div class="section-label">Notes</div>{{ $document['notes'] }}</div>
@endif

<table class="approval">
    <tr>
        <td><div class="signature">Prepared by / Date</div></td>
        <td class="right">
            <div class="signature" style="margin-left: auto">
                @if ($document['approved_by']){{ $document['approved_by'] }}@else Approved by / Date @endif
                @if ($document['approved_at'])<br><span class="muted">{{ $document['approved_at'] }}</span>@endif
            </div>
        </td>
    </tr>
</table>

<div class="footer">Generated by AutoERP | {{ $document['number'] }}</div>
</body>
</html>
