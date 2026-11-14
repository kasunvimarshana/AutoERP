<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document_title }} - {{ $payment_number }}</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        .receipt-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            padding: 20px;
        }
        .mode-pdf .receipt-container, .mode-print .receipt-container {
            padding: 0;
            max-width: 100%;
        }
        .screen-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            margin-bottom: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .screen-toolbar .title {
            font-weight: 700;
            font-size: 13px;
            color: #0f172a;
        }
        .screen-toolbar .actions {
            display: flex;
            gap: 8px;
        }
        .screen-toolbar button, .screen-toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .screen-toolbar button.primary {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .doc-title {
            font-size: 16px;
            font-weight: 800;
            text-align: right;
            color: {{ $is_inbound ? '#059669' : '#2563eb' }};
            letter-spacing: 0.05em;
        }
        .doc-meta {
            text-align: right;
            font-size: 11px;
            color: #475569;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-posted { background: #dcfce7; color: #166534; }
        .badge-approved { background: #e0f2fe; color: #075985; }
        .party-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .party-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .party-box td {
            vertical-align: top;
            font-size: 11px;
        }
        .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .value {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }
        .data-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 7px 10px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-box {
            margin-top: 10px;
            margin-bottom: 16px;
            display: flex;
            justify-content: flex-end;
        }
        .summary-table {
            width: 320px;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 10px;
            font-size: 11px;
        }
        .summary-table tr.total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 14px;
            font-weight: 800;
        }
        .words-box {
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 30px;
        }
        .sign-line {
            border-top: 1px dashed #64748b;
            padding-top: 6px;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
        }
        @media print {
            .screen-toolbar { display: none !important; }
            body { background: #fff !important; }
            .receipt-container { padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="mode-{{ $mode ?? 'html' }}">

<div class="receipt-container">
    @if(($mode ?? 'html') === 'html')
    <div class="screen-toolbar">
        <div>
            <div class="title">{{ $document_title }} · {{ $payment_number }}</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Status: <span class="badge badge-posted">{{ $posting_status }}</span> · Date: {{ $payment_date }}
            </div>
        </div>
        <div class="actions">
            <button type="button" class="primary" onclick="window.print()">
                Print Receipt
            </button>
            <button type="button" onclick="window.close()">
                Close
            </button>
        </div>
    </div>
    @endif

    <table class="header-table">
        <tr>
            <td>
                <div class="company-name">AUTOERP ENTERPRISE</div>
                <div style="font-size: 11px; color: #475569; margin-top: 3px;">
                    Vehicle Rental & Fleet Management Solutions
                </div>
                @if($agreement_reference)
                <div style="font-size: 11px; font-weight: 600; color: #2563eb; margin-top: 4px;">
                    Agreement Ref: {{ $agreement_reference }}
                </div>
                @endif
            </td>
            <td>
                <div class="doc-title">{{ $document_title }}</div>
                <div class="doc-meta">
                    <strong>Receipt #:</strong> {{ $payment_number }}<br>
                    <strong>Date:</strong> {{ $payment_date }}<br>
                    <strong>Status:</strong> <span class="badge badge-posted">{{ $posting_status }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="party-box">
        <table>
            <tr>
                <td style="width: 55%;">
                    <div class="label">{{ $party_role }}</div>
                    <div class="value">{{ $party['name'] }}</div>
                    @if(!empty($party['code']))
                    <div style="color: #64748b; font-size: 10px;">Account Code: {{ $party['code'] }}</div>
                    @endif
                    @if(!empty($party['phone']))
                    <div style="color: #64748b; font-size: 10px;">Phone: {{ $party['phone'] }}</div>
                    @endif
                </td>
                <td style="width: 45%;">
                    @if($reference_number)
                    <div class="label">Transaction Reference</div>
                    <div class="value" style="font-size: 11px;">{{ $reference_number }}</div>
                    @endif
                    @if($notes)
                    <div class="label" style="margin-top: 4px;">Notes</div>
                    <div style="color: #475569; font-size: 10px;">{{ $notes }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Payment Methods Breakdown -->
    <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-top: 14px;">
        PAYMENT METHOD DETAILS
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Method</th>
                <th>Details / Reference</th>
                <th class="text-right">Amount ({{ $currency['code'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
            <tr>
                <td style="font-weight: 600;">{{ $line['method_name'] }}</td>
                <td style="color: #475569;">{{ $line['details'] ?: '—' }}</td>
                <td class="text-right" style="font-weight: 700;">{{ number_format((float) $line['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Invoices Settled Breakdown -->
    @if(count($allocations) > 0)
    <div style="font-size: 11px; font-weight: 700; color: #0f172a; margin-top: 16px;">
        ALLOCATION TO INVOICES & BILLS
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Invoice / Bill #</th>
                <th>Invoice Date</th>
                <th class="text-right">Invoice Total</th>
                <th class="text-right">Balance Before</th>
                <th class="text-right">Paid Amount</th>
                <th class="text-right">Remaining Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allocations as $alloc)
            <tr>
                <td style="font-weight: 600; color: #2563eb;">{{ $alloc['invoice_number'] }}</td>
                <td>{{ $alloc['invoice_date'] }}</td>
                <td class="text-right">{{ number_format((float) $alloc['invoice_total'], 2) }}</td>
                <td class="text-right">{{ number_format((float) $alloc['balance_before'], 2) }}</td>
                <td class="text-right" style="font-weight: 700; color: #059669;">{{ number_format((float) $alloc['allocated_amount'], 2) }}</td>
                <td class="text-right" style="font-weight: 600;">{{ number_format((float) $alloc['balance_after'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Totals Summary Table -->
    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="text-right" style="color: #64748b;">Total Amount Settled:</td>
                <td class="text-right" style="font-weight: 600;">{{ $currency['symbol'] }} {{ number_format((float) $allocated_amount, 2) }}</td>
            </tr>
            @if((float) $unapplied_amount > 0)
            <tr>
                <td class="text-right" style="color: #059669; font-weight: 600;">Advance / Unapplied Surplus:</td>
                <td class="text-right" style="color: #059669; font-weight: 700;">{{ $currency['symbol'] }} {{ number_format((float) $unapplied_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="text-right">Total Payment:</td>
                <td class="text-right" style="color: {{ $is_inbound ? '#059669' : '#2563eb' }};">{{ $currency['symbol'] }} {{ number_format((float) $total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($amount_in_words))
    <div class="words-box">
        <strong>Amount in Words:</strong> {{ $amount_in_words }}
    </div>
    @endif

    <table class="signatures-table">
        <tr>
            <td>
                <div class="sign-line">{{ $is_inbound ? 'Customer / Payer Signature' : 'Authorized By' }}</div>
            </td>
            <td>
                <div class="sign-line">{{ $is_inbound ? 'Cashier / Authorized Signature' : 'Payee / Owner Signature' }}</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
