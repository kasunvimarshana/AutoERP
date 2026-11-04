<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header['report_title'] }} - {{ $header['duration'] }}</title>
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
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
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
        .meta-label { font-weight: 600; color: #333; }
        .meta-val { font-weight: 700; color: #000; }

        .kpi-cards {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .kpi-card {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            background: #f8fafc;
        }
        .kpi-title { font-size: 8.5px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .kpi-value { font-size: 13px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .data-table th, .data-table td {
            border: 1px solid #777;
            padding: 3px 4px;
        }
        .data-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: center;
            font-size: 8.5px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .nowrap { white-space: nowrap; }

        .footer-branding {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px solid #999;
            font-size: 8.5px;
            color: #444;
            display: flex;
            justify-content: space-between;
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
            📄 {{ $header['report_title'] }} Preview &mdash; {{ $header['duration'] }}
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

    <table class="chart-header-table">
        <tr>
            <td style="width: 70%;">
                <div class="company-name">{{ $header['company_name'] }}</div>
                <div class="report-subtitle">{{ $header['report_title'] }}</div>
            </td>
            <td style="width: 30%; text-align: right; font-size: 9.5px;">
                <span class="meta-label">Report Date:</span> <span class="meta-val">{{ $header['report_date'] }}</span>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="meta-label">Billing Coverage :</span> <span class="meta-val">{{ $header['duration'] }}</span>
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="meta-label">Currency :</span> <span class="meta-val">LKR (or Agreement Currency)</span>
            </td>
        </tr>
    </table>

    <!-- KPI Cards -->
    <div class="kpi-cards">
        <div class="kpi-card">
            <div class="kpi-title">Billed Agreements</div>
            <div class="kpi-value">{{ $summary['total_agreements'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Base Rent</div>
            <div class="kpi-value">{{ $summary['total_base_rent'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Excess Distance</div>
            <div class="kpi-value">{{ $summary['total_excess_mileage'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Overtime & Night Outs</div>
            <div class="kpi-value">{{ number_format((float)$summary['total_overtime'] + (float)$summary['total_night_out'], 2) }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Toll Disbursements</div>
            <div class="kpi-value">{{ $summary['total_toll_pass'] }}</div>
        </div>
        <div class="kpi-card" style="background: #eff6ff; border-color: #bfdbfe;">
            <div class="kpi-title" style="color: #1e40af;">Total Net Revenue</div>
            <div class="kpi-value" style="color: #1e3a8a;">{{ $summary['grand_total_revenue'] }}</div>
        </div>
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Agreement No</th>
                <th>Customer / Lessee</th>
                <th>Vehicle No</th>
                <th>Basis</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Base Rent</th>
                <th>Excess KM</th>
                <th>Overtime</th>
                <th>Night Out</th>
                <th>Toll Pass</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            <tr>
                <td class="text-center font-bold nowrap">{{ $row['agreement_reference'] }}</td>
                <td class="text-left">{{ $row['customer_name'] }}</td>
                <td class="text-center font-bold nowrap">{{ $row['vehicle_number'] }}</td>
                <td class="text-center">{{ $row['rental_basis'] }}</td>
                <td class="text-center">{{ $row['driver_mode'] }}</td>
                <td class="text-center">{{ $row['status'] }}</td>
                <td class="text-right">{{ $row['base_rent'] }}</td>
                <td class="text-right">{{ $row['excess_mileage'] }}</td>
                <td class="text-right">{{ $row['overtime'] }}</td>
                <td class="text-right">{{ $row['night_out'] }}</td>
                <td class="text-right">{{ $row['toll_pass'] }}</td>
                <td class="text-right font-bold">{{ $row['total_revenue'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 15px;">No customer agreements recorded for the selected billing period.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: 700; background: #f8fafc;">
                <td colspan="6" class="text-right" style="padding: 6px 4px;">
                    Totals ({{ $summary['total_agreements'] }} agreements)
                </td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['total_base_rent'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['total_excess_mileage'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['total_overtime'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['total_night_out'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['total_toll_pass'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px; color: #1e3a8a;">{{ $summary['grand_total_revenue'] }}</td>
            </tr>
        </tfoot>
    </table>

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
