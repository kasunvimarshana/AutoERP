@php($formatter = app(\Modules\Reporting\Services\ReportValueFormatter::class))
@if ($rows === [])
    <div class="empty-state">No records matched the selected report filters.</div>
@else
    <table class="report-table">
        <thead>
        <tr>
            @foreach ($definition->columns as $column)
                <th class="{{ in_array($column->format, ['money', 'currency', 'decimal', 'integer'], true) ? 'number' : '' }}">
                    {{ $column->label }}
                </th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                @foreach ($definition->columns as $column)
                    <td class="{{ in_array($column->format, ['money', 'currency', 'decimal', 'integer'], true) ? 'number' : '' }}">
                        {{ $formatter->format($row[$column->key] ?? null, $column->format) }}
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
