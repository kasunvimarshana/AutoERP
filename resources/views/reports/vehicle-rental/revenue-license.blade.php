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
            margin: 10mm 15mm 10mm 15mm;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.3;
        }
        .sheet-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 15px;
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
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .company-title {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .system-subtitle {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .report-name {
            font-size: 13px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .filter-desc {
            font-size: 10px;
            color: #333;
        }
        .kpi-row {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        .kpi-box {
            flex: 1;
            border: 1px solid #94a3b8;
            padding: 6px 10px;
            border-radius: 4px;
            background: #f8fafc;
        }
        .kpi-box .lbl {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }
        .kpi-box .val {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: middle;
        }
        table.data-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-align: left;
        }
        table.data-table td.align-center { text-align: center; }
        table.data-table td.align-right { text-align: right; }
        .badge-expired {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            font-size: 9px;
        }
        .badge-soon {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 9px;
        }
        .badge-active {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            background-color: #dcfce7;
            color: #166534;
            font-weight: 700;
            font-size: 9px;
        }
        .report-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #94a3b8;
            font-size: 9px;
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
            <button class="primary" onclick="window.print()">Print Report</button>
        </div>
    </div>
    @endif

    <div class="report-header">
        <div class="company-title">{{ $header['company_name'] }}</div>
        <div class="system-subtitle">{{ $header['system_title'] }}</div>
        <div class="report-name">{{ $header['report_title'] }}</div>
        <div class="filter-desc">{{ $header['period_description'] }}</div>
    </div>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="lbl">Total Fleet Checked</div>
            <div class="val">{{ $summary['total_vehicles'] }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Expired Licences</div>
            <div class="val" style="color: #b91c1c;">{{ $summary['expired_count'] }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Expiring in 30 Days</div>
            <div class="val" style="color: #d97706;">{{ $summary['expiring_soon_count'] }}</div>
        </div>
        <div class="kpi-box">
            <div class="lbl">Valid Licences</div>
            <div class="val" style="color: #15803d;">{{ $summary['active_count'] }}</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Vehicle No</th>
                <th style="width: 45%;">Owner's Name</th>
                <th style="width: 15%; text-align: center;">Expire Date</th>
                <th style="width: 15%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td style="font-weight: 700;">{{ $row['vehicle_no'] }}</td>
                <td>{{ $row['owner_name'] ?: '-' }}</td>
                <td class="align-center">{{ $row['expire_date'] }}</td>
                <td class="align-center">
                    @if($row['status'] === 'expired')
                        <span class="badge-expired">EXPIRED</span>
                    @elseif($row['status'] === 'expiring_soon')
                        <span class="badge-soon">DUE SOON</span>
                    @elseif($row['status'] === 'active')
                        <span class="badge-active">VALID</span>
                    @else
                        <span>{{ $row['status_label'] }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">
                    No vehicle revenue licences found for the selected criteria.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        <div>Software Provided by : AutoERP Rent A Car System</div>
        <div>Generated: {{ $header['generated_at'] }}</div>
    </div>
</div>
</body>
</html>
