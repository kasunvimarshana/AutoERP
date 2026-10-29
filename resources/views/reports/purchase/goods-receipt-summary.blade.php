@extends('reports.layouts.report')

@section('report-content')
    @php($formatter = app(\Modules\Reporting\Services\ReportValueFormatter::class))
    <style>
        .grn-summary .location-row td { background: #e9f1f6; color: #123149; font-weight: 700; border-top: 2px solid #0f3d5e; }
        .grn-summary .location-total td, .grn-summary .report-total td { font-weight: 700; border-top: 2px solid #64748b; }
        .grn-summary .report-total td { background: #dfeaf2; border-color: #0f3d5e; }
        .grn-summary .note-cell { white-space: pre-wrap; overflow-wrap: anywhere; }
    </style>

    <p>Posted GRNs only. Accepted quantity and cost are based on accepted units and unit cost before discounts, tax and charges. Quantity totals show only when every line has the same UOM.</p>

    @if($rows === [])
        <div class="empty-state">No posted goods receipts matched the selected filters.</div>
    @else
        <table class="report-table grn-summary">
            <thead>
                <tr>
                    <th>GRN</th>
                    <th>GRN date</th>
                    <th>Supplier</th>
                    <th>PO</th>
                    <th>Note</th>
                    <th class="number">Accepted qty</th>
                    <th class="number">Total cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @if($row['row_type'] === 'location')
                        <tr class="location-row"><td colspan="7">{{ $row['grn_number'] }}</td></tr>
                    @else
                        <tr class="{{ str_replace('_', '-', $row['row_type']) }}">
                            <td>{{ $row['grn_number'] }}</td>
                            <td>{{ $formatter->format($row['received_date'] ?? null, 'date') }}</td>
                            <td>{{ $row['supplier'] ?? '' }}</td>
                            <td>{{ $row['purchase_order'] ?? '' }}</td>
                            <td class="note-cell">{{ $row['note'] ?? '' }}</td>
                            <td class="number">{{ $row['accepted_quantity'] ?? '' }}</td>
                            <td class="number">{{ $formatter->format($row['total_cost'] ?? null, 'decimal') }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
