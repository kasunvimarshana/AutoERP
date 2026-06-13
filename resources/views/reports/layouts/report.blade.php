<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $definition->title }}</title>
    @php($paperSize = strtoupper((string) config('reporting.pdf.paper_size', 'A4')))
    <style>
        * { box-sizing: border-box; }
        @page {
            size: {{ $paperSize }} {{ $orientation }};
            margin:
                {{ (float) config('reporting.pdf.margins.top', 12) }}mm
                {{ (float) config('reporting.pdf.margins.right', 10) }}mm
                {{ (float) config('reporting.pdf.margins.bottom', 12) }}mm
                {{ (float) config('reporting.pdf.margins.left', 10) }}mm;
        }
        html, body { margin: 0; padding: 0; background: #fff; color: #172033; font-family: Arial, Helvetica, sans-serif; }
        body { font-size: 11px; line-height: 1.35; }
        .report-shell { width: 100%; }
        .screen-actions { padding: 12px; background: #f8fafc; border-bottom: 1px solid #cbd5e1; text-align: right; }
        .screen-actions button { border: 1px solid #94a3b8; border-radius: 4px; padding: 7px 12px; background: #fff; cursor: pointer; font-weight: 600; }
        .screen-actions button + button { margin-left: 8px; }
        .report-content { padding: 16px 20px 24px; }
        .mode-pdf .report-content, .mode-print .report-content { padding: 0; }
        .report-header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0f3d5e; margin-bottom: 14px; }
        .report-header td { vertical-align: top; padding: 0 0 12px; }
        .report-header .brand-cell { width: 55%; padding-right: 12px; }
        .report-header .report-title-cell { width: 45%; text-align: right; }
        .brand-table { width: 100%; border-collapse: collapse; }
        .brand-table td { padding: 0; vertical-align: middle; }
        .brand-table .logo-cell { width: 64px; padding-right: 12px; }
        .brand-logo { width: 52px; height: 52px; object-fit: contain; }
        .brand-title { font-size: 18px; line-height: 1.15; font-weight: 700; color: #0f3d5e; }
        .brand-subtitle { color: #64748b; margin-top: 3px; }
        .report-title-cell h1 { margin: 0; font-size: 20px; color: #172033; }
        .report-title-cell p { margin: 4px 0 0; color: #64748b; }
        .report-meta { margin-bottom: 14px; color: #64748b; }
        .report-meta span { display: inline-block; margin: 0 18px 5px 0; }
        .report-meta strong { color: #172033; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 4px; margin: -4px -4px 10px; page-break-inside: avoid; }
        .summary-table td { width: 25%; vertical-align: top; }
        .summary-card { border: 1px solid #cbd5e1; border-left: 3px solid #0f3d5e; padding: 8px 10px; page-break-inside: avoid; }
        .summary-card-empty { border: 0; }
        .summary-label { color: #64748b; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        .summary-value { margin-top: 3px; font-size: 13px; font-weight: 700; }
        .report-table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .report-table thead { display: table-header-group; }
        .report-table tfoot { display: table-footer-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th, .report-table td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; word-wrap: break-word; }
        .report-table th { background: #e9f1f6; color: #123149; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .025em; }
        .report-table td.number, .report-table th.number { text-align: right; white-space: nowrap; }
        .empty-state { padding: 24px; text-align: center; color: #64748b; border: 1px solid #cbd5e1; }
        .report-footer { width: 100%; border-collapse: collapse; margin-top: 14px; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 9px; }
        .report-footer td { padding-top: 8px; }
        .report-footer .footer-right { text-align: right; }
        .page-break { page-break-before: always; }
        .avoid-page-break { page-break-inside: avoid; }
        @if (count($definition->columns) > 12)
            .report-table { table-layout: fixed; font-size: 6.5px; }
            .report-table th, .report-table td { padding: 3px; }
            .report-table th { font-size: 6px; }
        @elseif (count($definition->columns) > 8)
            .report-table { table-layout: fixed; font-size: 8px; }
            .report-table th, .report-table td { padding: 4px; }
            .report-table th { font-size: 7px; }
        @endif
        @media print {
            .screen-actions { display: none !important; }
            .report-content { padding: 0; }
        }
        @media screen and (max-width: 760px) {
            .report-header .brand-cell, .report-header .report-title-cell { display: block; width: 100%; }
            .report-header .report-title-cell { padding-top: 14px; text-align: left; }
            .report-content { overflow-x: auto; }
        }
    </style>
</head>
<body class="mode-{{ $mode }}">
<div class="report-shell">
    @include('reports.shared.actions')
    <div class="report-content">
        @include('reports.shared.header')
        @section('report-content')
            @include('reports.shared.summary')
            @include('reports.shared.table')
        @show
        @include('reports.shared.footer')
    </div>
</div>
@if ($mode === 'print')
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
