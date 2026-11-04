<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header['report_title'] }} - {{ $header['agreement_number'] }}</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            size: A4 landscape;
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
            max-width: 1120px;
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
        .screen-toolbar button:hover, .screen-toolbar a:hover {
            background: #e2e8f0;
        }
        .screen-toolbar button.primary:hover, .screen-toolbar a.primary:hover {
            background: #0369a1;
        }

        /* Running chart layout */
        .chart-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .chart-header-table td {
            vertical-align: top;
            padding: 1px 0;
        }
        .company-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.2px;
            color: #000;
        }
        .report-subtitle {
            font-size: 10px;
            font-weight: 700;
            margin-top: 2px;
            color: #111;
        }
        .rental-mode-badge {
            font-size: 10px;
            font-weight: 700;
            text-align: right;
            color: #111;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 9.5px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .meta-label {
            font-weight: 600;
            color: #333;
        }
        .meta-val {
            font-weight: 700;
            color: #000;
        }

        /* Main Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .data-table th, .data-table td {
            border: 1px solid #777;
            padding: 2.5px 3px;
        }
        .data-table th {
            background: #f8fafc;
            font-weight: 700;
            text-align: center;
            font-size: 8.5px;
            color: #000;
        }
        .data-table tr.idle-day td {
            background: #fafafa;
            color: #444;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .nowrap { white-space: nowrap; }

        /* Column widths */
        .col-date { width: 68px; }
        .col-start { width: 55px; }
        .col-finish { width: 55px; }
        .col-comp { width: 65px; }
        .col-cumul { width: 65px; }
        .col-on { width: 56px; }
        .col-off { width: 56px; }
        .col-lessee { width: 110px; }
        .col-ot-hrs { width: 44px; }
        .col-ot-type { width: 32px; }
        .col-night { width: 48px; }
        .col-pass { width: 58px; }
        .col-remark { }

        /* Summary section */
        .summary-box-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9px;
        }
        .summary-box-table td {
            vertical-align: top;
            padding: 2px;
        }
        .summary-left-block {
            line-height: 1.6;
        }
        .summary-left-block div {
            display: flex;
            gap: 8px;
        }
        .footer-branding {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px solid #999;
            font-size: 8.5px;
            color: #444;
            display: flex;
            justify-content: space-between;
        }

        /* Signatures Block */
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 20px;
        }
        .sig-line {
            border-top: 1px dashed #444;
            padding-top: 4px;
            font-weight: 600;
            font-size: 8.5px;
        }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 8.5pt; }
            .data-table th, .data-table td { padding: 2px 3px; font-size: 7.5pt; }
        }
    </style>
</head>
<body class="mode-{{ $mode }}">
<div class="sheet-container">
    @if ($mode === 'preview')
    <div class="no-print screen-toolbar">
        <div class="title">
            📄 Running Chart Sheet Preview &mdash; {{ $header['agreement_number'] }} ({{ $header['client_name'] }})
        </div>
        <div class="actions">
            <button onclick="window.print()" class="primary">
                🖨️ Print
            </button>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" target="_blank">
                📥 Export PDF
            </a>
            <button onclick="window.close()">
                ✖ Close
            </button>
        </div>
    </div>
    @endif

    <!-- Header Section -->
    <table class="chart-header-table">
        <tr>
            <td style="width: 65%;">
                <div class="company-name">{{ $header['company_name'] }}</div>
                <div class="report-subtitle">{{ $header['report_title'] }}</div>
            </td>
            <td style="width: 35%;" class="rental-mode-badge">
                {{ $header['rental_mode'] }}
            </td>
        </tr>
    </table>

    <!-- Metadata Section -->
    <table class="meta-table">
        @if (!empty($header['is_owner_sheet']))
        <tr>
            <td style="width: 58%;">
                <span class="meta-label">Lessor ID :</span> <span class="meta-val">{{ $header['lessor_id'] }}</span>
            </td>
            <td style="width: 42%; text-align: right;">
                <span class="meta-label">Page</span> <span class="meta-val">{{ $header['page'] }}</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="meta-label">Report Date :</span> <span class="meta-val">{{ $header['report_date'] }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Name of Owner :</span> <span class="meta-val">{{ $header['owner_name'] }}</span>
            </td>
            <td style="text-align: right;">
                <span class="meta-label">Agreement No :</span> <span class="meta-val">{{ $header['agreement_number'] }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Duration :</span> <span class="meta-val">{{ $header['duration'] }}</span>
            </td>
            <td style="text-align: right;">
                <span class="meta-label">Agreement Vehicle No :</span> <span class="meta-val">{{ $header['agreement_vehicle_number'] }}</span>
            </td>
        </tr>
        @else
        <tr>
            <td style="width: 58%;">
                <span class="meta-label">Name of Client :</span> <span class="meta-val">{{ $header['client_name'] }}</span>
            </td>
            <td style="width: 42%; text-align: right;">
                <span class="meta-label">Page</span> <span class="meta-val">{{ $header['page'] }}</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="meta-label">Report Date :</span> <span class="meta-val">{{ $header['report_date'] }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">Duration :</span> <span class="meta-val">{{ $header['duration'] }}</span>
            </td>
            <td style="text-align: right;">
                <span class="meta-label">Agreement No :</span> <span class="meta-val">{{ $header['agreement_number'] }}</span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: right;">
                <span class="meta-label">Agreement Vehicle No :</span> <span class="meta-val">{{ $header['agreement_vehicle_number'] }}</span>
            </td>
        </tr>
        @endif
    </table>

    <!-- Main Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-date">Date</th>
                <th class="col-start">Start</th>
                <th class="col-finish">Finish</th>
                <th class="col-comp">Comparative<br>Mileage</th>
                <th class="col-cumul">Cumulative<br>Mileage</th>
                <th class="col-on">ON</th>
                <th class="col-off">OFF</th>
                @if (!empty($header['is_owner_sheet']))
                <th class="col-lessee">Lessee</th>
                @endif
                <th class="col-ot-hrs">OT Hrs</th>
                <th class="col-ot-type">OT<br>Type</th>
                <th class="col-night">Night Out</th>
                <th class="col-pass">Pass</th>
                <th class="col-remark">Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr class="{{ $row['is_active_day'] ? 'active-day' : 'idle-day' }}">
                <td class="text-center nowrap">{{ $row['date'] }}</td>
                <td class="text-right">{{ $row['start_odometer'] }}</td>
                <td class="text-right">{{ $row['finish_odometer'] }}</td>
                <td class="text-right font-bold">{{ $row['comparative_mileage'] > 0 ? $row['comparative_mileage'] : '0' }}</td>
                <td class="text-right font-bold">{{ $row['cumulative_mileage'] }}</td>
                <td class="text-center nowrap">{{ $row['on_time'] }}</td>
                <td class="text-center nowrap">{{ $row['off_time'] }}</td>
                @if (!empty($header['is_owner_sheet']))
                <td class="text-left nowrap">{{ $row['lessee'] }}</td>
                @endif
                <td class="text-right">{{ $row['ot_hours'] }}</td>
                <td class="text-center font-bold">{{ $row['ot_type'] }}</td>
                <td class="text-center">{{ $row['night_out'] }}</td>
                <td class="text-right">{{ $row['pass'] }}</td>
                <td class="text-left">{{ $row['remarks'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: 700; background: #f8fafc;">
                <td colspan="3" style="text-align: left; padding: 6px 4px; vertical-align: top;">
                    <div style="line-height: 1.5;">
                        <div>Excess KM &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : <strong>{{ $summary['excess_km'] }}</strong></div>
                        <div>Total Night Out &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : <strong>{{ $summary['total_night_out'] }}</strong></div>
                        <div>Total Garage KM &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : <strong>{{ $summary['total_garage_km'] }}</strong></div>
                    </div>
                </td>
                <td class="text-right" style="vertical-align: top; padding: 6px 4px;">
                    <div>{{ $summary['total_comparative_mileage'] }}</div>
                </td>
                <td class="text-right" style="vertical-align: top; padding: 6px 4px;">
                    <div>{{ $summary['final_cumulative_mileage'] }}</div>
                </td>
                <td colspan="{{ !empty($header['is_owner_sheet']) ? 3 : 2 }}" style="text-align: right; vertical-align: top; padding: 6px 4px;">
                    Normal OT Hrs
                </td>
                <td class="text-right" style="vertical-align: top; padding: 6px 4px;">
                    {{ $summary['total_normal_ot_hours'] }}
                </td>
                <td colspan="2" style="text-align: right; vertical-align: top; padding: 6px 4px;">
                    Total Pass
                </td>
                <td class="text-right font-bold" style="vertical-align: top; padding: 6px 4px;">
                    {{ $summary['total_pass'] }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-line">Driver Signature</div>
            </td>
            <td>
                <div class="sig-line">Checked By (Fleet Operations)</div>
            </td>
            <td>
                <div class="sig-line">{{ !empty($header['is_owner_sheet']) ? 'Owner / Authorized Signature' : 'Customer Authorized Signature' }}</div>
            </td>
        </tr>
    </table>

    <!-- Software Footer -->
    <div class="footer-branding">
        <div>{{ $footer['software_provider'] }}</div>
        <div>Generated on {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

@if ($mode === 'print')
<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
@endif
</body>
</html>
