@php($formatter = app(\Modules\Reporting\Services\ReportValueFormatter::class))
@if ($summary !== [])
    <section class="summary-grid avoid-page-break">
        @foreach ($summary as $item)
            <div class="summary-card">
                <div class="summary-label">{{ $item['label'] }}</div>
                <div class="summary-value">{{ $formatter->format($item['value'], $item['format']) }}</div>
            </div>
        @endforeach
    </section>
@endif
