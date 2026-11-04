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
            font-size: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #777;
            padding: 2.5px 3px;
        }
        .data-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: center;
            font-size: 8px;
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
            body { font-size: 8pt; }
            .data-table th, .data-table td { padding: 2px 2px; font-size: 7pt; }
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
                <span class="meta-label">Evaluation Period :</span> <span class="meta-val">{{ $header['duration'] }}</span>
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="meta-label">Ownership Filter :</span> <span class="meta-val">{{ ucfirst(str_replace('_', ' ', $filters['ownership_type'])) }}</span>
            </td>
        </tr>
    </table>

    <!-- KPI Cards -->
    <div class="kpi-cards">
        <div class="kpi-card">
            <div class="kpi-title">Fleet Evaluated</div>
            <div class="kpi-value">{{ $summary['total_vehicles_evaluated'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Gross Revenue</div>
            <div class="kpi-value">{{ $summary['fleet_total_revenue'] }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Direct Operating Costs</div>
            <div class="kpi-value">{{ $summary['fleet_total_direct_costs'] }}</div>
        </div>
        <div class="kpi-card" style="background: #f0fdf4; border-color: #bbf7d0;">
            <div class="kpi-title" style="color: #166534;">Fleet Gross Margin</div>
            <div class="kpi-value" style="color: #14532d;">{{ $summary['fleet_gross_margin'] }} ({{ $summary['fleet_margin_percentage'] }}%)</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Fleet Rev / Cost per KM</div>
            <div class="kpi-value">{{ $summary['fleet_avg_revenue_per_km'] }} / {{ $summary['fleet_avg_cost_per_km'] }}</div>
        </div>
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Vehicle No</th>
                <th>Make & Model</th>
                <th>Ownership</th>
                <th>Customer Revenue</th>
                <th>Owner Cost</th>
                <th>Fuel Cost</th>
                <th>Maintenance</th>
                <th>Toll & Other</th>
                <th>Total Costs</th>
                <th>Net Margin</th>
                <th>Margin %</th>
                <th>KM</th>
                <th>Rev/KM</th>
                <th>Cost/KM</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            <tr>
                <td class="text-center font-bold nowrap">{{ $row['registration_number'] }}</td>
                <td class="text-left">{{ $row['vehicle_make_model'] }}</td>
                <td class="text-center">{{ $row['ownership_type'] }}</td>
                <td class="text-right font-bold">{{ $row['customer_revenue'] }}</td>
                <td class="text-right">{{ $row['owner_lease_cost'] }}</td>
                <td class="text-right">{{ $row['fuel_cost'] }}</td>
                <td class="text-right">{{ $row['maintenance_cost'] }}</td>
                <td class="text-right">{{ number_format((float)$row['toll_cost'] + (float)$row['other_cost'], 2) }}</td>
                <td class="text-right font-bold text-rose-700">{{ $row['total_direct_costs'] }}</td>
                <td class="text-right font-bold" style="color: {{ (float)$row['gross_margin'] >= 0 ? '#15803d' : '#b91c1c' }};">
                    {{ $row['gross_margin'] }}
                </td>
                <td class="text-right font-bold">{{ $row['margin_percentage'] }}%</td>
                <td class="text-right">{{ number_format($row['distance_km']) }}</td>
                <td class="text-right">{{ $row['revenue_per_km'] }}</td>
                <td class="text-right">{{ $row['cost_per_km'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" class="text-center" style="padding: 15px;">No vehicles found for profitability analysis in this period.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; font-weight: 700; background: #f8fafc;">
                <td colspan="3" class="text-right" style="padding: 6px 4px;">
                    Totals ({{ $summary['total_vehicles_evaluated'] }} vehicles)
                </td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['fleet_total_revenue'] }}</td>
                <td colspan="4" class="text-right" style="padding: 6px 4px;">Total Operating Costs:</td>
                <td class="text-right font-bold text-rose-700" style="padding: 6px 4px;">{{ $summary['fleet_total_direct_costs'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px; color: #15803d;">{{ $summary['fleet_gross_margin'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['fleet_margin_percentage'] }}%</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ number_format($summary['fleet_total_distance_km']) }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['fleet_avg_revenue_per_km'] }}</td>
                <td class="text-right font-bold" style="padding: 6px 4px;">{{ $summary['fleet_avg_cost_per_km'] }}</td>
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
