<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header['report_title'] }} - {{ $header['cycle_number'] }}</title>
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
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }
        .statement-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 16px;
        }
        .mode-pdf .statement-container, .mode-print .statement-container {
            padding: 0;
            max-width: 100%;
        }
        .screen-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            margin-bottom: 16px;
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
        .screen-toolbar button.primary, .screen-toolbar a.primary {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 800;
            color: #1e3a8a;
            text-align: right;
            text-transform: uppercase;
        }
        .doc-meta {
            font-size: 9px;
            color: #475569;
            text-align: right;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-finalized { background: #dcfce7; color: #166534; }
        .badge-stage1 { background: #dbeafe; color: #1e40af; }
        .badge-draft { background: #f1f5f9; color: #475569; }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .info-grid td {
            padding: 6px 10px;
            font-size: 9.5px;
            vertical-align: top;
            width: 50%;
        }
        .info-label {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 12px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        table.data-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background: #fafafa;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }
        .summary-box td {
            vertical-align: top;
        }
        .financial-summary-table {
            width: 100%;
            max-width: 340px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .financial-summary-table td {
            padding: 3px 6px;
        }
        .financial-summary-table tr.total-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-weight: 800;
            font-size: 11px;
            padding: 5px 6px;
        }
        .financial-summary-table tr.balance-row td {
            background: #fef2f2;
            color: #991b1b;
            font-weight: 800;
            font-size: 11px;
            padding: 5px 6px;
            border-bottom: 2px solid #dc2626;
        }

        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            font-size: 8px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
        .signatures {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 0 15px;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            margin-top: 40px;
            padding-top: 4px;
            font-size: 8.5px;
            font-weight: 600;
        }
    </style>
</head>
<body class="mode-{{ $mode ?? 'screen' }}">

@if (($mode ?? 'screen') === 'screen')
<div class="screen-toolbar">
    <div class="title">Rental Billing Statement · {{ $header['cycle_number'] }}</div>
    <div class="actions">
        <button type="button" onclick="window.print()">Print Statement</button>
        <a href="?format=pdf" class="primary" target="_blank">Download PDF</a>
    </div>
</div>
@endif

<div class="statement-container">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $header['company_name'] }}</div>
                <div class="company-sub">Enterprise Vehicle Rental Operations & Fleet Financial Management</div>
            </td>
            <td style="width: 40%;">
                <div class="doc-title">{{ $header['report_title'] }}</div>
                <div class="doc-meta">
                    <strong>Cycle #:</strong> {{ $header['cycle_number'] }}<br>
                    <strong>Date:</strong> {{ $header['as_of_date'] }}<br>
                    <strong>Status:</strong>
                    <span class="badge {{ $header['status'] === 'FINALIZED' ? 'badge-finalized' : 'badge-stage1' }}">
                        {{ $header['status'] }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Party & Agreement Details -->
    <table class="info-grid">
        <tr>
            <td>
                <span class="info-label">Customer / Commercial Party</span>
                <div class="info-val" style="font-size: 11px;">{{ $party['name'] }}</div>
                <div>Code: {{ $party['code'] }}</div>
                @if (!empty($party['address']) && $party['address'] !== '—')
                    <div>Address: {{ $party['address'] }}</div>
                @endif
                @if (!empty($party['phone']) && $party['phone'] !== '—')
                    <div>Phone: {{ $party['phone'] }}</div>
                @endif
            </td>
            <td>
                <span class="info-label">Agreement & Billing Period</span>
                <div class="info-val">Agreement Ref: {{ $agreement['reference'] }}</div>
                <div>Contract Term: {{ $agreement['starts_on'] }} to {{ $agreement['ends_on'] }}</div>
                <div><strong>Billing Cycle Period:</strong> {{ $agreement['period_from'] }} – {{ $agreement['period_until'] }}</div>
                <div>Strategy: {{ $header['finalization_mode'] === 'supplementary_invoice' ? 'Mode A (Supplementary Usage)' : 'Mode B (Consolidated Revision)' }}</div>
            </td>
        </tr>
    </table>

    <!-- Vehicle Attribution Breakdown -->
    @if (!empty($vehicle_attributions) && count($vehicle_attributions) > 0)
    <div class="section-title">
        <span>Fleet Vehicle Attribution ({{ count($vehicle_attributions) }} Vehicle{{ count($vehicle_attributions) > 1 ? 's' : '' }})</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Role</th>
                <th class="text-center">Days</th>
                <th class="text-right">KM Run</th>
                <th class="text-right">Norm OT (h)</th>
                <th class="text-right">Dbl OT (h)</th>
                <th class="text-right">Night Outs</th>
                <th class="text-right">Base Rent ({{ $agreement['currency'] }})</th>
                <th class="text-right">OT / Allow ({{ $agreement['currency'] }})</th>
                <th class="text-right">Vehicle Total ({{ $agreement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vehicle_attributions as $va)
            <tr>
                <td class="font-bold">{{ $va['vehicle_label'] }}</td>
                <td>
                    @if ($va['is_replacement'])
                        <span style="color: #d97706; font-weight: 600;">Replacement</span>
                    @else
                        <span>Primary</span>
                    @endif
                </td>
                <td class="text-center">{{ $va['custody_days'] }}</td>
                <td class="text-right">{{ number_format((float) $va['total_km'], 1) }}</td>
                <td class="text-right">{{ number_format((float) $va['normal_ot_hours'], 1) }}</td>
                <td class="text-right">{{ number_format((float) $va['double_ot_hours'], 1) }}</td>
                <td class="text-right">{{ $va['night_outs'] }}</td>
                <td class="text-right">{{ number_format((float) $va['allocated_base_rent'], 2) }}</td>
                <td class="text-right">{{ number_format((float) $va['allocated_ot_amount'] + (float) $va['allocated_night_out_amount'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format((float) $va['subtotal'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Driver Attribution Breakdown -->
    @if (!empty($driver_attributions) && count($driver_attributions) > 0)
    <div class="section-title">
        <span>Driver Telematics & Overtime Attributions</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Driver Name</th>
                <th>Employee / Ref #</th>
                <th class="text-center">Trips</th>
                <th class="text-right">KM Run</th>
                <th class="text-right">Norm OT (h)</th>
                <th class="text-right">Dbl OT (h)</th>
                <th class="text-right">Night Outs</th>
                <th class="text-right">OT Amount ({{ $agreement['currency'] }})</th>
                <th class="text-right">Night Outs ({{ $agreement['currency'] }})</th>
                <th class="text-right">Driver Total ({{ $agreement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($driver_attributions as $da)
            <tr>
                <td class="font-bold">{{ $da['driver_name'] }}</td>
                <td>{{ $da['driver_reference'] ?: '—' }}</td>
                <td class="text-center">{{ $da['charts_count'] }}</td>
                <td class="text-right">{{ number_format((float) $da['total_km'], 1) }}</td>
                <td class="text-right">{{ number_format((float) $da['normal_ot_hours'], 1) }}</td>
                <td class="text-right">{{ number_format((float) $da['double_ot_hours'], 1) }}</td>
                <td class="text-right">{{ $da['night_outs'] }}</td>
                <td class="text-right">{{ number_format((float) $da['normal_ot_amount'] + (float) $da['double_ot_amount'], 2) }}</td>
                <td class="text-right">{{ number_format((float) $da['night_out_amount'], 2) }}</td>
                <td class="text-right font-bold">{{ number_format((float) $da['total_earnings'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Attached Running Charts Schedule -->
    @if (!empty($charts) && count($charts) > 0)
    <div class="section-title">
        <span>Attached Running Charts Telematics Log ({{ count($charts) }} Trips)</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Chart #</th>
                <th>Trip Window</th>
                <th>Vehicle #</th>
                <th>Driver</th>
                <th class="text-right">KM</th>
                <th class="text-right">Norm OT (min)</th>
                <th class="text-right">Dbl OT (min)</th>
                <th class="text-right">Night Outs</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($charts as $c)
            <tr>
                <td class="font-bold">{{ $c['reference'] }}</td>
                <td>{{ $c['starts_at'] }} – {{ $c['ends_at'] }}</td>
                <td>
                    {{ $c['vehicle_number'] }}
                    @if ($c['is_replacement']) <span style="color:#d97706; font-size:7.5px;">[R]</span> @endif
                </td>
                <td>{{ $c['driver_name'] }}</td>
                <td class="text-right">{{ number_format((float) $c['commercial_km'], 1) }}</td>
                <td class="text-right">{{ $c['normal_ot_minutes'] }}</td>
                <td class="text-right">{{ $c['double_ot_minutes'] }}</td>
                <td class="text-right">{{ $c['night_outs'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Ad-hoc Additions and Deductions -->
    @if (!empty($items) && count($items) > 0)
    <div class="section-title">
        <span>Ad-Hoc Additions & Contractual Deductions</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Rate ({{ $agreement['currency'] }})</th>
                <th class="text-right">Amount ({{ $agreement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
            <tr>
                <td>
                    <span class="badge {{ $item['item_type'] === 'addition' ? 'badge-finalized' : 'badge-draft' }}">
                        {{ ucfirst($item['item_type']) }}
                    </span>
                </td>
                <td class="font-bold">{{ ucwords(str_replace('_', ' ', $item['category'])) }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">{{ number_format((float) $item['quantity'], 2) }}</td>
                <td class="text-right">{{ number_format((float) $item['unit_rate'], 2) }}</td>
                <td class="text-right font-bold" style="{{ $item['item_type'] === 'deduction' ? 'color:#dc2626;' : '' }}">
                    {{ $item['item_type'] === 'deduction' ? '-' : '' }}{{ number_format((float) $item['amount'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Fleet Substitutions & Driver Reassignment History -->
    @if (!empty($reassignments) && count($reassignments) > 0)
    <div class="section-title">
        <span>Assignment Audit History (Substitutions & Handovers)</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Reason</th>
                <th class="text-right">Financial Delta ({{ $agreement['currency'] }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reassignments as $r)
            <tr>
                <td>{{ $r['occurred_at'] }}</td>
                <td class="font-bold">{{ ucwords(str_replace('_', ' ', $r['type'])) }}</td>
                <td>{{ $r['previous_vehicle'] ?: ($r['previous_driver'] ?: '—') }}</td>
                <td>{{ $r['new_vehicle'] ?: ($r['new_driver'] ?: '—') }}</td>
                <td>{{ $r['reason'] }}</td>
                <td class="text-right">{{ number_format((float) $r['financial_delta'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Financial Statement Totals & Invoices -->
    <table class="summary-box">
        <tr>
            <td style="width: 50%;">
                <div class="section-title" style="margin-top:0;">
                    <span>Generated Invoices & Receivables</span>
                </div>
                <table class="data-table" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th class="text-right">Total ({{ $agreement['currency'] }})</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($invoices['initial'])
                        <tr>
                            <td>Stage 1 Base Rent</td>
                            <td class="font-bold">{{ $invoices['initial']['number'] }}</td>
                            <td>{{ $invoices['initial']['date'] }}</td>
                            <td class="text-right">{{ number_format((float) $invoices['initial']['total'], 2) }}</td>
                            <td>{{ ucfirst($invoices['initial']['status']) }}</td>
                        </tr>
                        @endif
                        @if ($invoices['supplementary'])
                        <tr>
                            <td>Stage 2 Supplementary</td>
                            <td class="font-bold">{{ $invoices['supplementary']['number'] }}</td>
                            <td>{{ $invoices['supplementary']['date'] }}</td>
                            <td class="text-right">{{ number_format((float) $invoices['supplementary']['total'], 2) }}</td>
                            <td>{{ ucfirst($invoices['supplementary']['status']) }}</td>
                        </tr>
                        @endif
                        @if ($invoices['final'])
                        <tr>
                            <td>Stage 2 Consolidated</td>
                            <td class="font-bold">{{ $invoices['final']['number'] }}</td>
                            <td>{{ $invoices['final']['date'] }}</td>
                            <td class="text-right">{{ number_format((float) $invoices['final']['total'], 2) }}</td>
                            <td>{{ ucfirst($invoices['final']['status']) }}</td>
                        </tr>
                        @endif
                        @if (!$invoices['initial'] && !$invoices['supplementary'] && !$invoices['final'])
                        <tr>
                            <td colspan="5" class="text-center" style="color:#64748b; font-style:italic;">No formal financial invoice issued yet.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; padding-left: 20px;">
                <table class="financial-summary-table">
                    <tr>
                        <td>Base Rental Amount:</td>
                        <td class="text-right font-bold">{{ number_format((float) $financials['base_rent_amount'], 2) }}</td>
                    </tr>
                    @if ((float) $financials['excess_km_amount'] > 0)
                    <tr>
                        <td>Excess Distance Charges:</td>
                        <td class="text-right">{{ number_format((float) $financials['excess_km_amount'], 2) }}</td>
                    </tr>
                    @endif
                    @if ((float) $financials['overtime_amount'] > 0)
                    <tr>
                        <td>Driver Overtime (Normal + Double):</td>
                        <td class="text-right">{{ number_format((float) $financials['overtime_amount'], 2) }}</td>
                    </tr>
                    @endif
                    @if ((float) $financials['night_out_amount'] > 0)
                    <tr>
                        <td>Driver Night-Out Allowances:</td>
                        <td class="text-right">{{ number_format((float) $financials['night_out_amount'], 2) }}</td>
                    </tr>
                    @endif
                    @if ((float) $financials['tolls_amount'] > 0)
                    <tr>
                        <td>Highway Tolls:</td>
                        <td class="text-right">{{ number_format((float) $financials['tolls_amount'], 2) }}</td>
                    </tr>
                    @endif
                    @if ((float) $financials['additions_amount'] > 0)
                    <tr>
                        <td>Ad-Hoc Additions (Fuel/Salaries/Charges):</td>
                        <td class="text-right">{{ number_format((float) $financials['additions_amount'], 2) }}</td>
                    </tr>
                    @endif
                    @if ((float) $financials['deductions_amount'] > 0)
                    <tr>
                        <td>Less: Contract Deductions & Rebates:</td>
                        <td class="text-right" style="color: #dc2626;">-{{ number_format((float) $financials['deductions_amount'], 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Billed Net Amount:</td>
                        <td class="text-right">{{ $agreement['currency'] }} {{ number_format((float) $financials['total_billed_amount'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Settled Payments:</td>
                        <td class="text-right font-bold" style="color: #166534;">{{ $agreement['currency'] }} {{ number_format((float) $financials['total_paid_amount'], 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td>Remaining Balance Due:</td>
                        <td class="text-right">{{ $agreement['currency'] }} {{ number_format((float) $financials['balance_due'], 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared By (Rental Officer)</div>
            </td>
            <td>
                <div class="sig-line">Verified By (Fleet Accountant)</div>
            </td>
            <td>
                <div class="sig-line">Authorized Signatory / Client Acknowledgement</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <span>AutoERP Fleet Operations Management System · Audit Sealed</span>
        <span>Generated on {{ date('d/m/Y H:i:s') }}</span>
        <span>Page 1 of 1</span>
    </div>
</div>

</body>
</html>
