<div class="document-title">
    <div class="title-box">{{ $document['title'] }}</div>
</div>

@if (! empty($layout['header_content']))
    <div class="header-content-banner" style="margin-bottom: 8px; padding: 6px 10px; background: #f3f4f6; border-left: 3px solid {{ $layout['settings']['primary_color'] ?? '#111827' }}; font-size: 11px; color: #374151;">
        {{ $layout['header_content'] }}
    </div>
@endif

@foreach (($document['warnings'] ?? []) as $warning)
    <div class="warning">{{ $warning }}</div>
@endforeach

@php
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];
    $minimumPrintableLineRows = 5;
    $blankLineRows = max(0, $minimumPrintableLineRows - count($lines));
    $summaryColumnSpan = $usesFocusedPrint ? 3 : 4;
    $settlementRowsVisible = ! $zeroMoney($amounts['paid_total'])
        || ! $zeroMoney($amounts['credit_total'])
        || bccomp((string) $amounts['balance_due']['raw'], (string) $amounts['grand_total']['raw'], 6) !== 0;

    $settings = $layout['settings'] ?? [];
    $showLogo = (bool) ($settings['show_company_logo'] ?? $settings['show_logo'] ?? true);
    $showTax = (bool) ($settings['show_tax_summary'] ?? $settings['show_tax_breakdown'] ?? true);
    $showPayment = (bool) ($settings['show_payment_details'] ?? $settings['show_payment_stub'] ?? true);
    $showSignatures = (bool) ($settings['show_signatures'] ?? true);
    $showWords = (bool) ($settings['show_amount_in_words'] ?? true);
    $showNotes = (bool) ($settings['show_notes'] ?? true);
    $showQr = (bool) ($settings['show_qr_code'] ?? false);
    $customNotes = ! empty($settings['notes']) ? $settings['notes'] : ($document['notes'] ?? null);
    $customTerms = ! empty($settings['terms']) ? $settings['terms'] : ($document['payment_terms'] ?? null);
@endphp

<table class="field-table">
    <tr>
        <td class="field-cell">
            <span class="label">Date of Invoice:</span>
            {{ $document['invoice_date'] ?? '' }}
        </td>
        <td class="gap"></td>
        <td class="field-cell">
            <span class="label">{{ $document['number_label'] }}</span>
            {{ $document['invoice_number'] }}
        </td>
    </tr>
</table>

<table class="field-table">
    <tr>
        @foreach (['supplier' => 'Supplier', 'purchaser' => 'Purchaser'] as $key => $label)
            @php($party = $document[$key])
            <td class="party-cell">
                <div class="party-line"><span class="label">{{ $label }}'s TIN:</span> {{ $party['tin'] ?? '' }}</div>
                @if (! empty($party['vat_registration_number']))
                    <div class="party-line"><span class="label">VAT Registration No:</span> {{ $party['vat_registration_number'] }}</div>
                @endif
                @if (! empty($party['svat_registration_number']))
                    <div class="party-line"><span class="label">SVAT Registration No:</span> {{ $party['svat_registration_number'] }}</div>
                @endif
                <div class="party-line"><span class="label">{{ $label }}'s Name:</span> {{ $party['name'] }}</div>
                <div class="party-address"><span class="label">Address:</span> {{ $party['address'] ?? '' }}</div>
                <div class="party-phone"><span class="label">Telephone No:</span> {{ $party['phone'] ?? '' }}</div>
                @if ($key === 'purchaser' && ! empty($document['purchaser_reference_fields']))
                    <div class="purchaser-reference-fields">
                        @foreach ($document['purchaser_reference_fields'] as $field)
                            <div class="party-line"><span class="label">{{ $field['label'] }}:</span> {{ $field['value'] }}</div>
                        @endforeach
                    </div>
                @endif
            </td>
            @if ($key === 'supplier')
                <td class="gap"></td>
            @endif
        @endforeach
    </tr>
</table>

<table class="field-table">
    <tr>
        <td class="field-cell">
            <span class="label">Date of Delivery / Supply:</span>
            {{ $document['supply_date'] ?? '' }}
            @if (! empty($document['supply_period_start']) || ! empty($document['supply_period_end']))
                <div class="muted">
                    Supply period: {{ $document['supply_period_start'] ?? '' }}
                    @if (! empty($document['supply_period_end'])) to {{ $document['supply_period_end'] }} @endif
                </div>
            @endif
        </td>
        <td class="gap"></td>
        <td class="field-cell">
            <span class="label">Place of Supply:</span>
            {{ $document['place_of_supply'] ?? '' }}
        </td>
    </tr>
</table>

@if ($showNotes && (! empty($document['due_date']) || ! empty($customNotes) || ($showWords && ! empty($document['amount_in_words']))))
    <table class="field-table">
        <tr>
            <td class="additional-field">
                <span class="label">Additional Information if any:</span>
                <div class="additional-content">
                    @if (! empty($document['due_date']))<div>Due date: {{ $document['due_date'] }}</div>@endif
                    @if ($showWords && ! empty($document['amount_in_words']))<div>Amount in words: <em>{{ $document['amount_in_words'] }}</em></div>@endif
                    @if (! empty($customNotes))<div>{{ $customNotes }}</div>@endif
                </div>
            </td>
        </tr>
    </table>
@endif

<table class="invoice-lines">
    <thead>
    <tr>
        @if ($usesFocusedPrint)
            <th>Item Name</th>
        @else
            <th style="width: 13%;">Reference</th>
            <th>Description of Goods or Services</th>
        @endif
        <th style="width: 12%;">Quantity</th>
        <th style="width: 14%;">Unit Price</th>
        <th style="width: 17%;">Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($lines as $line)
        <tr>
            @if ($usesFocusedPrint)
                <td>{{ $line['display_name'] }}</td>
            @else
                <td>{{ $line['reference'] }}</td>
                <td>
                    {{ $line['description'] }}
                    @if (! empty($line['item']))<div class="muted">{{ $line['item'] }}</div>@endif
                </td>
            @endif
            <td class="number">
                {{ $line['quantity']['display'] }}
            </td>
            <td class="number">{{ $line['unit_price']['display'] }}</td>
            <td class="number">{{ $line['line_total']['display'] }}</td>
        </tr>
    @endforeach
    @for ($row = 0; $row < $blankLineRows; $row++)
        <tr><td>&nbsp;</td>@unless ($usesFocusedPrint)<td></td>@endunless<td></td><td></td><td></td></tr>
    @endfor

    @if ($document['shows_service_discount_breakdown'])
        @foreach (['line_discount_total', 'bill_discount_total'] as $amountKey)
            <tr>
                <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts[$amountKey]['label'] }}:</td>
                <td class="number">@unless ($zeroMoney($amounts[$amountKey]))-@endunless{{ $amounts[$amountKey]['display'] }}</td>
            </tr>
        @endforeach
    @endif
    @unless ($usesFocusedPrint)
        <tr>
            <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['subtotal']['label'] }}:</td>
            <td class="number">{{ $amounts['subtotal']['display'] }}</td>
        </tr>
        @foreach (['discount_total', 'charge_total', 'adjustment_total'] as $amountKey)
            @if (! $zeroMoney($amounts[$amountKey]))
                <tr>
                    <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts[$amountKey]['label'] }}:</td>
                    <td class="number">{{ $amounts[$amountKey]['display'] }}</td>
                </tr>
            @endif
        @endforeach
        @if ($showTax)
            <tr>
                <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['tax_total']['label'] }}:</td>
                <td class="number">{{ $amounts['tax_total']['display'] }}</td>
            </tr>
        @endif
    @endunless
    <tr>
        <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['grand_total']['label'] }}:</td>
        <td class="number">{{ $amounts['grand_total']['display'] }}</td>
    </tr>
    @if ($settlementRowsVisible || $usesFocusedPrint)
        @if (! $zeroMoney($amounts['paid_total']))
            <tr><td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['paid_total']['label'] }}:</td><td class="number">{{ $amounts['paid_total']['display'] }}</td></tr>
        @endif
        <tr><td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['balance_due']['label'] }}:</td><td class="number">{{ $amounts['balance_due']['display'] }}</td></tr>
    @endif
    </tbody>
</table>

@if ($showPayment)
    <table class="footer-fields">
        <tr>
            <td>
                <span class="label">Mode of Payment:</span> {{ $document['resolved_payment_mode'] ?? '' }}
                @if (! empty($customTerms))
                    <div class="muted">Terms: {{ $customTerms }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif

@if (! empty($layout['footer_content']))
    <div class="footer-content-notice" style="margin-top: 8px; padding: 6px 10px; background: #fafafa; border-top: 1px solid #e5e7eb; font-size: 10px; color: #4b5563; text-align: center;">
        {{ $layout['footer_content'] }}
    </div>
@endif
