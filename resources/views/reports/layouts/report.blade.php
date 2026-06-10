<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $definition->title }}</title>
    <style>
        :root {
            --brand: #0f3d5e;
            --brand-soft: #e9f1f6;
            --border: #cbd5e1;
            --muted: #64748b;
            --text: #172033;
        }
        * { box-sizing: border-box; }
        @page { size: A4 {{ $orientation }}; margin: 12mm 10mm; }
        html, body { margin: 0; padding: 0; background: #fff; color: var(--text); font-family: Arial, Helvetica, sans-serif; }
        body { font-size: 11px; line-height: 1.35; }
        .report-shell { width: 100%; }
        .screen-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 12px; background: #f8fafc; border-bottom: 1px solid var(--border); }
        .screen-actions button { border: 1px solid #94a3b8; border-radius: 4px; padding: 7px 12px; background: #fff; cursor: pointer; font-weight: 600; }
        .report-content { padding: 16px 20px 24px; }
        .report-header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid var(--brand); padding-bottom: 12px; margin-bottom: 14px; }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-logo { width: 52px; height: 52px; object-fit: contain; }
        .brand-title { font-size: 18px; line-height: 1.15; font-weight: 700; color: var(--brand); }
        .brand-subtitle { color: var(--muted); margin-top: 3px; }
        .report-title { text-align: right; }
        .report-title h1 { margin: 0; font-size: 20px; color: var(--text); }
        .report-title p { margin: 4px 0 0; color: var(--muted); max-width: 360px; }
        .report-meta { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-bottom: 14px; color: var(--muted); }
        .report-meta strong { color: var(--text); }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 14px; }
        .summary-card { border: 1px solid var(--border); border-left: 3px solid var(--brand); border-radius: 3px; padding: 8px 10px; break-inside: avoid; }
        .summary-label { color: var(--muted); font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        .summary-value { margin-top: 3px; font-size: 13px; font-weight: 700; }
        .report-table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .report-table thead { display: table-header-group; }
        .report-table tfoot { display: table-footer-group; }
        .report-table tr { break-inside: avoid; page-break-inside: avoid; }
        .report-table th, .report-table td { border: 1px solid var(--border); padding: 5px 6px; vertical-align: top; overflow-wrap: anywhere; }
        .report-table th { background: var(--brand-soft); color: #123149; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .025em; }
        .report-table td.number, .report-table th.number { text-align: right; white-space: nowrap; }
        .empty-state { padding: 24px; text-align: center; color: var(--muted); border: 1px solid var(--border); }
        .report-footer { display: flex; justify-content: space-between; gap: 16px; margin-top: 14px; padding-top: 8px; border-top: 1px solid var(--border); color: var(--muted); font-size: 9px; }
        .page-break { break-before: page; page-break-before: always; }
        .avoid-page-break { break-inside: avoid; page-break-inside: avoid; }
        @media print {
            .screen-actions { display: none !important; }
            .report-content { padding: 0; }
            .report-footer { display: none; }
        }
        @media screen and (max-width: 760px) {
            .report-header { display: block; }
            .report-title { margin-top: 14px; text-align: left; }
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .report-content { overflow-x: auto; }
        }
    </style>
</head>
<body>
<div class="report-shell">
    @include('reports.shared.actions')
    <main class="report-content">
        @include('reports.shared.header')
        @section('report-content')
            @include('reports.shared.summary')
            @include('reports.shared.table')
        @show
        @include('reports.shared.footer')
    </main>
</div>
@if ($mode === 'print')
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
