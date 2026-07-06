<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $document['invoice_number'] }} - Invoice</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 16mm;
        }
        .controls {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
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
        .btn.secondary {
            background: #fff;
            color: #111827;
        }
        .document-header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .document-title,
        .document-meta {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0;
        }
        .muted { color: #4b5563; }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table th,
        .meta-table td {
            padding: 2px 0 2px 10px;
            text-align: left;
            vertical-align: top;
        }
        .meta-table th {
            width: 42%;
            color: #4b5563;
            font-weight: 600;
        }
        .party-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            table-layout: fixed;
        }
        .party-card {
            display: table-cell;
            width: 50%;
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: top;
        }
        .party-card + .party-card { border-left: 0; }
        .section-title {
            margin-bottom: 6px;
            color: #374151;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .party-name {
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 700;
        }
        .kv {
            width: 100%;
            border-collapse: collapse;
        }
        .kv th,
        .kv td {
            padding: 1px 0;
            text-align: left;
            vertical-align: top;
        }
        .kv th {
            width: 32%;
            color: #6b7280;
            font-weight: 600;
        }
        .lines,
        .totals {
            width: 100%;
            border-collapse: collapse;
        }
        .lines th,
        .lines td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }
        .lines th {
            background: #f9fafb;
            color: #374151;
            font-size: 11px;
            font-weight: 700;
        }
        .number { text-align: right; white-space: nowrap; }
        .totals-wrap {
            width: 42%;
            margin: 14px 0 0 auto;
        }
        .totals td {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px 0 5px 8px;
        }
        .totals td:first-child {
            color: #374151;
        }
        .totals tr.grand td {
            border-bottom: 2px solid #111827;
            font-size: 13px;
            font-weight: 700;
        }
        .notes {
            margin-top: 16px;
            border-top: 1px solid #d1d5db;
            padding-top: 10px;
        }
        @media print {
            body { background: #fff; }
            .sheet {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    $isPdf = ($mode ?? 'print') === 'pdf';
    $currency = $document['currency']['code'] ?? $document['currency']['symbol'] ?? null;
    $amounts = $document['amounts'];
@endphp
<div class="sheet">
    @if (! $isPdf)
        <div class="controls no-print">
            <a href="#" id="printBtn" class="btn secondary">Print</a>
            @if (! empty($pdf_url))
                <a href="{{ $pdf_url }}" class="btn" target="_blank" rel="noopener">Download PDF</a>
            @endif
        </div>
    @endif

    <div class="document-header">
        <div class="document-title">
            <h1>{{ $document['title'] }}</h1>
            <div class="muted">{{ $document['invoice_type'] }} / {{ $document['direction'] }}</div>
        </div>
        <div class="document-meta">
            <table class="meta-table">
                <tr>
                    <th>Invoice no.</th>
                    <td>{{ $document['invoice_number'] }}</td>
                </tr>
                <tr>
                    <th>Invoice date</th>
                    <td>{{ $document['invoice_date'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Due date</th>
                    <td>{{ $document['due_date'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $document['status'] }}</td>
                </tr>
                <tr>
                    <th>Currency</th>
                    <td>{{ $currency ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="party-grid">
        @foreach (['supplier' => 'Supplier', 'purchaser' => 'Purchaser'] as $key => $label)
            @php($party = $document[$key])
            <div class="party-card">
                <div class="section-title">{{ $label }}</div>
                <div class="party-name">{{ $party['name'] }}</div>
                <table class="kv">
                    @if (! empty($party['number']))
                        <tr>
                            <th>Number</th>
                            <td>{{ $party['number'] }}</td>
                        </tr>
                    @endif
                    @if (! empty($party['code']) && $party['code'] !== ($party['number'] ?? null))
                        <tr>
                            <th>Code</th>
                            <td>{{ $party['code'] }}</td>
                        </tr>
                    @endif
                    @if (! empty($party['tax_registration_number']))
                        <tr>
                            <th>Tax no.</th>
                            <td>{{ $party['tax_registration_number'] }}</td>
                        </tr>
                    @endif
                    @if (! empty($party['phone']))
                        <tr>
                            <th>Phone</th>
                            <td>{{ $party['phone'] }}</td>
                        </tr>
                    @endif
                    @if (! empty($party['email']))
                        <tr>
                            <th>Email</th>
                            <td>{{ $party['email'] }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endforeach
    </div>

    <table class="lines">
        <thead>
        <tr>
            <th style="width:6%">#</th>
            <th style="width:18%">Item</th>
            <th>Description</th>
            <th style="width:10%">Qty</th>
            <th style="width:12%">Unit price</th>
            <th style="width:10%">Discount</th>
            <th style="width:10%">Tax</th>
            <th style="width:10%">Charges</th>
            <th style="width:12%">Line total</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($document['lines'] as $line)
            <tr>
                <td class="number">{{ $line['line_number'] }}</td>
                <td>{{ $line['item'] ?? '-' }}</td>
                <td>{{ $line['description'] }}</td>
                <td class="number">
                    {{ $line['quantity']['display'] }}
                    @if (! empty($line['uom']))
                        {{ $line['uom'] }}
                    @endif
                </td>
                <td class="number">{{ $line['unit_price']['display'] }}</td>
                <td class="number">{{ $line['discount_amount']['display'] }}</td>
                <td class="number">{{ $line['tax_amount']['display'] }}</td>
                <td class="number">{{ $line['charge_amount']['display'] }}</td>
                <td class="number">{{ $line['line_total']['display'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No invoice lines were recorded.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="totals-wrap">
        <table class="totals">
            @foreach (['subtotal', 'discount_total', 'tax_total', 'charge_total', 'adjustment_total'] as $key)
                <tr>
                    <td>{{ $amounts[$key]['label'] }}</td>
                    <td class="number">{{ $amounts[$key]['display'] }}</td>
                </tr>
            @endforeach
            <tr class="grand">
                <td>{{ $amounts['grand_total']['label'] }}</td>
                <td class="number">{{ $amounts['grand_total']['display'] }}</td>
            </tr>
            <tr>
                <td>{{ $amounts['paid_total']['label'] }}</td>
                <td class="number">{{ $amounts['paid_total']['display'] }}</td>
            </tr>
            <tr>
                <td>{{ $amounts['credit_total']['label'] }}</td>
                <td class="number">{{ $amounts['credit_total']['display'] }}</td>
            </tr>
            <tr>
                <td>{{ $amounts['balance_due']['label'] }}</td>
                <td class="number">{{ $amounts['balance_due']['display'] }}</td>
            </tr>
        </table>
    </div>

    @if (! empty($document['notes']))
        <div class="notes">
            <div class="section-title">Notes</div>
            <div>{{ $document['notes'] }}</div>
        </div>
    @endif
</div>
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
