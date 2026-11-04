<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header['report_title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.25;
        }
        .sheet-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            padding: 10px;
        }
        .mode-pdf .sheet-container, .mode-print .sheet-container {
            padding: 0;
            max-width: 100%;
        }
        .screen-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            margin-bottom: 15px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
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
            gap: 4px;
            background: #fff;
            color: #0f172a;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .screen-toolbar button.primary, .screen-toolbar a.primary {
            background: #0284c7;
            color: #fff;
            border-color: #0284c7;
        }
        @media print {
            .screen-toolbar { display: none !important; }
        }
        .report-header {
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }
        .company-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }
        .system-subtitle {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        .report-name {
            font-size: 12px;
            font-weight: 700;
            text-decoration: underline;
        }
        .kpi-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        .kpi-box {
            flex: 1;
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            border-radius: 4px;
            background: #f8fafc;
        }
        .kpi-box .lbl {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }
        .kpi-box .val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }
        .lessee-block {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .lessee-header {
            background-color: #f1f5f9;
            border: 1px solid #000;
            border-bottom: none;
            padding: 4px 8px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #e2e8f0;
            font-weight: 700;
            text-align: left;
        }
        table.data-table td.align-center { text-align: center; }
        table.data-table td.align-right { text-align: right; }
        tr.subtotal-row td {
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
            font-weight: 700;
            background-color: #fafafa;
        }
        tr.grand-total-row td {
            border-top: 2px solid #000;
            border-bottom: 3px double #000;
            font-weight: 900;
            font-size: 11px;
            background-color: #f1f5f9;
        }
        .report-footer {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #94a3b8;
            font-size: 8.5px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body class="mode-{{ $mode ?? 'preview' }}">
<div class="sheet-container">
    @if(($mode ?? 'preview') === 'preview')
    <div class="screen-toolbar">
        <div class="title">{{ $header['report_title'] }}</div>
        <div class="actions">
            <button class="primary" onclick="window.print()">Print Analysis</button>
        </div>
    </div>
    @endif

    <div class="report-header">
        <div class="company-title">{{ $header['company_name'] }}</div>
        <div class="system-subtitle">{{ $header['system_title'] }}</div>
        <div class="report-name">{{ $header['report_title'] }}</div>
    </div>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="lbl">Active Lessees</div>
            <div class="val">{{ $summary['total_lessees_count'] }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Overdue Invoices</div>
            <div class="val">{{ $summary['total_invoices_count'] }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Total Invoiced</div>
            <div class="val">{{ number_format((float) $summary['grand_total_original_amount'], 2) }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Total Outstanding</div>
            <div class="val" style="color: #b91c1c;">{{ number_format((float) $summary['grand_total_balance_amount'], 2) }}</div>
        </div>
    </div>

    @forelse($groups as $grp)
    <div class="lessee-block">
        <div class="lessee-header">
            <div><strong>{{ $grp['lessee_code'] }}</strong> {{ $grp['lessee_name'] }}</div>
            <div style="font-weight: normal; font-size: 9px;">
                @if($grp['phone']) Tel: {{ $grp['phone'] }} @endif
                @if($grp['contact_person']) &nbsp;|&nbsp; Contact: {{ $grp['contact_person'] }} @endif
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Txn. Type</th>
                    <th style="width: 10%; text-align: center;">Date</th>
                    <th style="width: 15%;">Debit Note / Invoice No</th>
                    <th style="width: 25%;">Agreement / Period</th>
                    <th style="width: 12%;">Vehicle No</th>
                    <th style="width: 13%; text-align: right;">Original Amount</th>
                    <th style="width: 13%; text-align: right;">Balance Amount</th>
                    <th style="width: 8%; text-align: center;">No of Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grp['invoices'] as $inv)
                <tr>
                    <td>{{ $inv['txn_type'] }}</td>
                    <td class="align-center">{{ $inv['invoice_date'] }}</td>
                    <td style="font-weight: 700;">{{ $inv['invoice_number'] }}</td>
                    <td>{{ $inv['agreement_duration'] }}</td>
                    <td style="font-weight: 600;">{{ $inv['vehicle_no'] }}</td>
                    <td class="align-right">{{ number_format((float) $inv['original_amount'], 2) }}</td>
                    <td class="align-right" style="font-weight: 700;">{{ number_format((float) $inv['balance_amount'], 2) }}</td>
                    <td class="align-center">{{ $inv['age_days'] }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="5" style="text-align: right; text-transform: uppercase;">Subtotal for {{ $grp['lessee_name'] }}:</td>
                    <td class="align-right">{{ number_format((float) $grp['subtotal_original_amount'], 2) }}</td>
                    <td class="align-right" style="color: #b91c1c;">{{ number_format((float) $grp['subtotal_balance_amount'], 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    @empty
    <div style="text-align: center; padding: 30px; border: 1px dashed #cbd5e1; color: #64748b; font-size: 11px;">
        No outstanding customer balances found as of {{ $header['as_of_date'] }}.
    </div>
    @endforelse

    @if(count($groups) > 0)
    <table class="data-table" style="margin-top: 10px;">
        <tbody>
            <tr class="grand-total-row">
                <td colspan="5" style="text-align: right; width: 72%;">GRAND TOTAL OUTSTANDING:</td>
                <td class="align-right" style="width: 13%;">{{ number_format((float) $summary['grand_total_original_amount'], 2) }}</td>
                <td class="align-right" style="width: 13%; color: #b91c1c;">{{ number_format((float) $summary['grand_total_balance_amount'], 2) }}</td>
                <td style="width: 8%;"></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="report-footer">
        <div>Software Provided by : AutoERP Rent A Car System</div>
        <div>Generated: {{ $header['generated_at'] }}</div>
    </div>
</div>
</body>
</html>
