@extends('reports.layouts.report')

@section('report-content')
    @php($formatter = app(\Modules\Reporting\Services\ReportValueFormatter::class))
    <style>
        .purchase-note { margin: 0 0 12px; color: #475569; }
        .purchase-detail .supplier-row td { background: #e9f1f6; color: #123149; font-weight: 700; border-top: 2px solid #0f3d5e; }
        .purchase-detail .order-row td { background: #f8fafc; font-weight: 600; border-top: 1px solid #94a3b8; }
        .purchase-detail .order-total td { font-weight: 700; border-top: 1px solid #64748b; }
        .purchase-detail .supplier-total td, .purchase-detail .report-total td { font-weight: 700; background: #eef2f7; border-top: 2px solid #0f3d5e; }
        .purchase-detail .report-total td { background: #dfeaf2; }
        .purchase-detail .supplier-row, .purchase-detail .order-row { page-break-after: avoid; }
    </style>

    <p class="purchase-note">PO dates determine which orders appear. GRN quantities and costs include posted receipts, excluding reversed GRNs. Total GRN cost is accepted quantity multiplied by unit cost, before discounts, tax and charges. Returns remain in receipt history.</p>

    @if($rows === [])
        <div class="empty-state">No purchase orders matched the selected filters.</div>
    @else
        <table class="report-table purchase-detail">
            <thead>
                <tr>
                    <th>Item / PO</th>
                    <th class="number">Cost price</th>
                    <th class="number">Order qty</th>
                    <th class="number">GRN qty</th>
                    <th class="number">Total GRN cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @if($row['row_type'] === 'supplier' || $row['row_type'] === 'order')
                        <tr class="{{ $row['row_type'] === 'supplier' ? 'supplier-row' : 'order-row' }}">
                            <td colspan="5">{{ $row['description'] }}</td>
                        </tr>
                    @else
                        <tr class="{{ str_replace('_', '-', $row['row_type']) }}">
                            <td>{{ $row['description'] }}</td>
                            <td class="number">{{ $formatter->format($row['unit_price'] ?? null, 'decimal') }}</td>
                            <td class="number">{{ $formatter->format($row['ordered_quantity'] ?? null, 'decimal') }}</td>
                            <td class="number">{{ $formatter->format($row['grn_quantity'] ?? null, 'decimal') }}</td>
                            <td class="number">{{ $formatter->format($row['grn_cost'] ?? null, 'decimal') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
