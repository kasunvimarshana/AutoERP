{{--
AutoERP Enterprise System

@author Kasun Vimarshana <kasunvmail@gmail.com>

Phone: +94713525253
--}}
@php
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];
    $settlementRowsVisible = ! $zeroMoney($amounts['paid_total'])
        || ! $zeroMoney($amounts['credit_total'])
        || bccomp((string) $amounts['balance_due']['raw'], (string) $amounts['grand_total']['raw'], 6) !== 0;

    $settings = $layout['settings'] ?? [];
    $primaryColor = $settings['primary_color'] ?? '#111827';
    $showLogo = (bool) ($settings['show_company_logo'] ?? $settings['show_logo'] ?? true);
    $showTax = (bool) ($settings['show_tax_summary'] ?? $settings['show_tax_breakdown'] ?? true);
    $showPayment = (bool) ($settings['show_payment_details'] ?? $settings['show_payment_stub'] ?? true);
    $showSignatures = (bool) ($settings['show_signatures'] ?? true);
    $showNotes = (bool) ($settings['show_notes'] ?? true);
    $showWords = (bool) ($settings['show_amount_in_words'] ?? false);
    $customNotes = ! empty($settings['notes']) ? $settings['notes'] : ($document['notes'] ?? null);
    $customTerms = ! empty($settings['terms']) ? $settings['terms'] : ($document['payment_terms'] ?? null);
@endphp

<div class="service-content">
<table class="invoice-header">
    <tr>
        <td>
            <div class="company-name">{{ $document['capitalizes_company_header'] ? mb_strtoupper($document['supplier']['name']) : $document['supplier']['name'] }}</div>
        </td>
        <td class="invoice-heading">{{ $document['title'] }}</td>
    </tr>
</table>

@if (! empty($layout['header_content']))
    <div style="margin-bottom: 2mm; font-size: 9px; color: #475569; font-style: italic;">
        {{ $layout['header_content'] }}
    </div>
@endif

<table class="identity-table">
    <tr>
        <td><strong>Date of invoice:</strong> {{ $document['invoice_date_display'] ?? $document['invoice_date'] ?? '' }}</td>
        <td><strong>{{ $document['number_label'] }}</strong> {{ $document['invoice_number'] }}</td>
    </tr>
</table>

<table class="party-table">
    <tr>
        @foreach (['supplier' => 'Supplier', 'purchaser' => 'Purchaser'] as $key => $label)
            @php
                $party = $document[$key];
                $addressBreak = $key === 'supplier' && ! empty($party['address'])
                    ? mb_strrpos($party['address'], ',')
                    : false;
            @endphp
            <td>
                @if (! empty($party['tin']))<div class="party-detail"><strong>{{ $label }}'s TIN:</strong> {{ $party['tin'] }}</div>@endif
                @if (! empty($party['vat_registration_number']))<div class="party-detail"><strong>VAT Registration No:</strong> {{ $party['vat_registration_number'] }}</div>@endif
                <div class="party-detail"><strong>{{ $label }}'s Name:</strong> {{ $party['name'] }}</div>
                @if (! empty($party['address']))
                    <div class="party-detail"><strong>Address:</strong>
                        @if ($addressBreak !== false && trim(mb_substr($party['address'], $addressBreak + 1)) !== '')
                            <span class="service-address-lines">{{ trim(mb_substr($party['address'], 0, $addressBreak + 1)) }}<br>{{ trim(mb_substr($party['address'], $addressBreak + 1)) }}</span>
                        @else
                            {{ $party['address'] }}
                        @endif
                    </div>
                @endif
                @if (! empty($party['phone']))<div class="party-detail"><strong>Telephone:</strong> {{ $party['phone'] }}</div>@endif
                @if ($key === 'purchaser' && ! empty($document['purchaser_reference_fields']))
                    @php
                        $referenceRows = ['primary' => [], 'mileage' => []];
                        $mileageLabels = ['Mileage', 'Current Mileage', 'Next Service Mileage'];
                        foreach ($document['purchaser_reference_fields'] as $field) {
                            $referenceRows[in_array($field['label'], $mileageLabels, true) ? 'mileage' : 'primary'][] = $field;
                        }
                    @endphp
                    @foreach ($referenceRows as $referenceRow)
                        @if ($referenceRow !== [])
                            <div class="service-inline-references">
                                @foreach ($referenceRow as $field)
                                    <span><strong>{{ $field['label'] === 'Mileage' ? 'Current Mileage' : $field['label'] }}:</strong> {{ $field['value'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif
            </td>
        @endforeach
    </tr>
</table>

<table class="service-lines">
    <thead>
        <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 45%;">Description of goods / services</th>
            <th style="width: 8%;">Qty</th>
            <th style="width: 14%;">Unit price</th>
            <th style="width: 13%;">Discount</th>
            <th style="width: 15%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            <tr>
                <td class="line-number">{{ $loop->iteration }}</td>
                <td>
                    {{ $line['display_name'] ?? $line['description'] }}
                </td>
                <td class="number">{{ $line['quantity']['display'] }}</td>
                <td class="number">{{ $line['unit_price']['display'] }}</td>
                <td class="number">{{ $line['discount_amount']['display'] }}</td>
                <td class="number">{{ $line['line_total']['display'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="settlement-table">
    <tr>
        <td class="payment-box">
            @if ($showPayment)
                <div class="payment-box-title">Payment details</div>
                <div><strong>Payment method:</strong> {{ $document['resolved_payment_mode'] ?? 'Not recorded' }}</div>
                <div><strong>Paid amount:</strong> {{ $amounts['paid_total']['display'] }}</div>
                @if (! empty($customTerms))<div><strong>Terms:</strong> {{ $customTerms }}</div>@endif
            @endif
            @if ($showWords && ! empty($document['amount_in_words']))
                <div style="margin-top: 2px;"><strong>Words:</strong> <em>{{ $document['amount_in_words'] }}</em></div>
            @endif
            @if ($showNotes && ! empty($customNotes))<div><strong>Additional information:</strong> {{ $customNotes }}</div>@endif
        </td>
        <td class="totals-box">
            <table>
                <tr><td>Subtotal before discounts</td><td>{{ $amounts['subtotal']['display'] }}</td></tr>
                <tr><td>Line discount</td><td>{{ $amounts['line_discount_total']['display'] }}</td></tr>
                <tr><td>Bill discount</td><td>{{ $amounts['bill_discount_total']['display'] }}</td></tr>
                @if ($showTax)
                    @unless ($zeroMoney($amounts['tax_total']))<tr><td>{{ $amounts['tax_total']['label'] }}</td><td>{{ $amounts['tax_total']['display'] }}</td></tr>@endunless
                @endif
                @unless ($zeroMoney($amounts['charge_total']))<tr><td>Charges</td><td>{{ $amounts['charge_total']['display'] }}</td></tr>@endunless
                @unless ($zeroMoney($amounts['adjustment_total']))<tr><td>Other adjustments</td><td>{{ $amounts['adjustment_total']['display'] }}</td></tr>@endunless
                <tr class="total-row"><td>Total amount</td><td>{{ $amounts['grand_total']['display'] }}</td></tr>
                <tr><td>Paid</td><td>{{ $amounts['paid_total']['display'] }}</td></tr>
                <tr class="total-row"><td>Balance due</td><td>{{ $amounts['balance_due']['display'] }}</td></tr>
            </table>
        </td>
    </tr>
</table>

@if (! empty($layout['footer_content']))
    <div style="margin-top: 2mm; text-align: center; font-size: 9px; color: #475569;">
        {{ $layout['footer_content'] }}
    </div>
@endif

<div class="service-policy">
    <strong>Non Refundable, Exchange possible only within 7 days of purchase</strong>
    <div>( This invoice cannot be used for a VAT input Claim )</div>
</div>
</div>

@if ($showSignatures)
    <table class="signatures">
        <tr>
            <td><div class="signature-rule">Customer</div></td>
            <td><div class="signature-rule">Cashier</div></td>
        </tr>
    </table>
@endif
