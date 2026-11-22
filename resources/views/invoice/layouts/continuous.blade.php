<table class="compact-header">
    <tr>
        <td class="compact-company">
            <div class="compact-company-name">{{ $document['capitalizes_company_header'] ? mb_strtoupper($document['supplier']['name']) : $document['supplier']['name'] }}</div>
            @if (! empty($document['supplier']['address']))<div>{{ $document['supplier']['address'] }}</div>@endif
            <div>
                @if (! empty($document['supplier']['phone']))Tel: {{ $document['supplier']['phone'] }}@endif
                @if (! empty($document['supplier']['vat_registration_number'])) | VAT: {{ $document['supplier']['vat_registration_number'] }}@endif
            </div>
        </td>
        <td>
            <div class="compact-title">{{ $document['title'] }}</div>
            <div class="compact-meta"><span class="label">{{ $document['number_label'] }}</span> {{ $document['invoice_number'] }}</div>
            <div><span class="label">Date:</span> {{ $document['invoice_date'] ?? '' }}</div>
        </td>
    </tr>
</table>

@foreach (($document['warnings'] ?? []) as $warning)
    <div class="warning">{{ $warning }}</div>
@endforeach

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

<table class="field-table">
    <tr>
        <td class="additional-field">
            <span class="label">Additional Information if any:</span>
            @if (! empty($document['due_date']) || ! empty($document['notes']))
                <div class="additional-content">
                    @if (! empty($document['due_date']))<div>Due date: {{ $document['due_date'] }}</div>@endif
                    @if (! empty($document['notes']))<div>{{ $document['notes'] }}</div>@endif
                </div>
            @endif
        </td>
    </tr>
</table>

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
        @unless ($usesFocusedPrint)<th style="width: 12%;">Discount</th>@endunless
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
            @unless ($usesFocusedPrint)<td class="number">{{ $line['discount_amount']['display'] }}</td>@endunless
            <td class="number">{{ $line['line_total']['display'] }}</td>
        </tr>
    @endforeach
    @for ($row = 0; $row < $blankLineRows; $row++)
        <tr><td>&nbsp;</td>@unless ($usesFocusedPrint)<td></td>@endunless<td></td><td></td>@unless ($usesFocusedPrint)<td></td>@endunless<td></td></tr>
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
        <tr>
            <td colspan="{{ $summaryColumnSpan }}" class="summary-label">{{ $amounts['tax_total']['label'] }}:</td>
            <td class="number">{{ $amounts['tax_total']['display'] }}</td>
        </tr>
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

<table class="footer-fields">
    <tr>
        <td>
            <span class="label">Mode of Payment:</span> {{ $document['resolved_payment_mode'] ?? '' }}
            @if (! empty($document['payment_terms']))
                <div class="muted">Terms: {{ $document['payment_terms'] }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="signature-table">
    <tr>
        <td><div class="signature-line">Prepared by</div></td>
        <td><div class="signature-line">Authorized by</div></td>
        <td><div class="signature-line">Customer signature</div></td>
    </tr>
</table>
