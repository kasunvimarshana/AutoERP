<table class="invoice-header">
    <tr>
        <td>
            <div class="company-name">{{ $document['capitalizes_company_header'] ? mb_strtoupper($document['supplier']['name']) : $document['supplier']['name'] }}</div>
            <div class="company-contact">
                {{ $document['supplier']['address'] ?? '' }}
                @if (! empty($document['supplier']['phone'])) | {{ $document['supplier']['phone'] }} @endif
            </div>
        </td>
        <td class="invoice-heading">{{ $document['title'] }}</td>
    </tr>
</table>

<table class="identity-table">
    <tr>
        <td><strong>Date of invoice:</strong> {{ $document['invoice_date_display'] ?? '' }}</td>
        <td><strong>{{ $document['number_label'] }}</strong> {{ $document['invoice_number'] }}</td>
    </tr>
</table>

<table class="party-table">
    <tr>
        @foreach (['supplier' => 'Supplier', 'purchaser' => 'Purchaser'] as $key => $label)
            @php($party = $document[$key])
            <td>
                @if (! empty($party['tin']))<div class="party-detail"><strong>{{ $label }}'s TIN:</strong> {{ $party['tin'] }}</div>@endif
                @if (! empty($party['vat_registration_number']))<div class="party-detail"><strong>VAT Registration No:</strong> {{ $party['vat_registration_number'] }}</div>@endif
                <div class="party-detail"><strong>{{ $label }}'s Name:</strong> {{ $party['name'] }}</div>
                @if (! empty($party['address']))<div class="party-detail"><strong>Address:</strong> {{ $party['address'] }}</div>@endif
                @if (! empty($party['phone']))<div class="party-detail"><strong>Telephone:</strong> {{ $party['phone'] }}</div>@endif
            </td>
        @endforeach
    </tr>
</table>

@if (! empty($document['purchaser_reference_fields']))
    <table class="service-references">
        @foreach (array_chunk($document['purchaser_reference_fields'], 4) as $referenceRow)
            <tr>
                @foreach ($referenceRow as $field)
                    <td>
                        <span class="reference-label">{{ $field['label'] }}:</span> {{ $field['value'] }}
                    </td>
                @endforeach
                @for ($empty = count($referenceRow); $empty < 4; $empty++)<td></td>@endfor
            </tr>
        @endforeach
    </table>
@endif

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
                    {{ $line['display_name'] }}
                    @if (! empty($line['item_code']))<span class="item-code">{{ $line['item_code'] }}</span>@endif
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
            <div class="payment-box-title">Payment details</div>
            <div><strong>Payment method:</strong> {{ $document['resolved_payment_mode'] ?? 'Not recorded' }}</div>
            <div><strong>Paid amount:</strong> {{ $amounts['paid_total']['display'] }}</div>
            @if (! empty($document['payment_terms']))<div><strong>Terms:</strong> {{ $document['payment_terms'] }}</div>@endif
            @if (! empty($document['notes']))<div><strong>Additional information:</strong> {{ $document['notes'] }}</div>@endif
        </td>
        <td class="totals-box">
            <table>
                <tr><td>Subtotal before discounts</td><td>{{ $amounts['subtotal']['display'] }}</td></tr>
                <tr><td>Line discount</td><td>{{ $amounts['line_discount_total']['display'] }}</td></tr>
                <tr><td>Bill discount</td><td>{{ $amounts['bill_discount_total']['display'] }}</td></tr>
                @unless ($zeroMoney($amounts['tax_total']))<tr><td>{{ $amounts['tax_total']['label'] }}</td><td>{{ $amounts['tax_total']['display'] }}</td></tr>@endunless
                @unless ($zeroMoney($amounts['charge_total']))<tr><td>Charges</td><td>{{ $amounts['charge_total']['display'] }}</td></tr>@endunless
                @unless ($zeroMoney($amounts['adjustment_total']))<tr><td>Other adjustments</td><td>{{ $amounts['adjustment_total']['display'] }}</td></tr>@endunless
                <tr class="total-row"><td>Total amount</td><td>{{ $amounts['grand_total']['display'] }}</td></tr>
                <tr><td>Paid</td><td>{{ $amounts['paid_total']['display'] }}</td></tr>
                <tr class="total-row"><td>Balance due</td><td>{{ $amounts['balance_due']['display'] }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="service-policy">
    <strong>Non Refundable, Exchange possible only within 7 days of purchase</strong>
    <div>( This invoice cannot be used for a VAT input Claim )</div>
</div>

<table class="signatures">
    <tr>
        <td><div class="signature-rule">Customer</div></td>
        <td><div class="signature-rule">Cashier</div></td>
    </tr>
</table>
