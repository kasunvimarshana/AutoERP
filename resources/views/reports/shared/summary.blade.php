@php($formatter = app(\Modules\Reporting\Services\ReportValueFormatter::class))
@if ($summary !== [])
    <table class="summary-table avoid-page-break" role="presentation">
        @foreach (array_chunk($summary, 4) as $summaryRow)
            <tr>
                @foreach ($summaryRow as $item)
                    <td class="summary-card">
                        <div class="summary-label">{{ $item['label'] }}</div>
                        <div class="summary-value">{{ $formatter->format($item['value'], $item['format']) }}</div>
                    </td>
                @endforeach
                @for ($empty = count($summaryRow); $empty < 4; $empty++)
                    <td class="summary-card-empty"></td>
                @endfor
            </tr>
        @endforeach
    </table>
@endif
