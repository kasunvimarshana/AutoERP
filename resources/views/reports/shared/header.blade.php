<table class="report-header avoid-page-break" role="presentation">
    <tr>
        <td class="brand-cell">
            <table class="brand-table" role="presentation">
                <tr>
                    @if (! empty($branding['logo_data_uri']))
                        <td class="logo-cell">
                            <img class="brand-logo" src="{{ $branding['logo_data_uri'] }}" alt="">
                        </td>
                    @endif
                    <td>
                        <div class="brand-title">{{ $branding['tenant_name'] ?? $branding['company_name'] }}</div>
                        <div class="brand-subtitle">
                            {{ $branding['organization_unit_name'] ?? $branding['company_name'] }}
                            @if (! empty($branding['organization_unit_code']))
                                ({{ $branding['organization_unit_code'] }})
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td class="report-title-cell">
            <h1>{{ $definition->title }}</h1>
            @if ($definition->description !== '')
                <p>{{ $definition->description }}</p>
            @endif
        </td>
    </tr>
</table>
<div class="report-meta avoid-page-break">
    <span><strong>Generated:</strong> {{ $generatedAt->format('Y-m-d H:i:s') }}</span>
    @if (! empty($branding['currency_code']))
        <span><strong>Currency:</strong> {{ $branding['currency_code'] }}</span>
    @endif
    @foreach ($filters as $filter)
        <span><strong>{{ $filter['label'] }}:</strong> {{ $filter['value'] }}</span>
    @endforeach
</div>
