@php
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];
    $showTax = (bool) ($layout['settings']['show_tax_summary'] ?? true);
    $showPayment = (bool) ($layout['settings']['show_payment_details'] ?? true);
@endphp

<div class="thermal-receipt" style="width: 76mm; margin: 0 auto; font-family: 'Courier New', Courier, monospace; font-size: 11px; line-height: 1.25; color: #000;">
    <!-- Store Header -->
    <div style="text-align: center; margin-bottom: 2mm;">
        <div style="font-size: 14px; font-weight: bold; text-transform: uppercase;">{{ $document['supplier']['name'] }}</div>
        @if (! empty($document['supplier']['address']))
            <div style="font-size: 10px; margin-top: 1px;">{{ $document['supplier']['address'] }}</div>
        @endif
        @if (! empty($document['supplier']['phone']))
            <div style="font-size: 10px;">Tel: {{ $document['supplier']['phone'] }}</div>
        @endif
        @if (! empty($document['supplier']['tin']) || ! empty($document['supplier']['vat_registration_number']))
            <div style="font-size: 9px; margin-top: 1px;">
                @if (! empty($document['supplier']['tin']))TIN: {{ $document['supplier']['tin'] }}@endif
                @if (! empty($document['supplier']['vat_registration_number'])) | VAT: {{ $document['supplier']['vat_registration_number'] }}@endif
            </div>
        @endif
    </div>

    <!-- Divider -->
    <div style="border-top: 1px dashed #000; margin: 2mm 0;"></div>

    <!-- Receipt Meta -->
    <div style="font-size: 10px; margin-bottom: 2mm;">
        <div style="display: flex; justify-content: space-between;">
            <span>{{ $document['number_label'] }}:</span>
            <span style="font-weight: bold;">{{ $document['invoice_number'] }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Date:</span>
            <span>{{ $document['invoice_date_display'] ?? $document['invoice_date'] }}</span>
        </div>
        @if (! empty($document['purchaser']['name']))
            <div style="margin-top: 1px;">
                <span>Customer: {{ $document['purchaser']['name'] }}</span>
            </div>
        @endif
    </div>

    <!-- Divider -->
    <div style="border-top: 1px dashed #000; margin: 2mm 0;"></div>

    <!-- Items -->
    <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left; padding: 2px 0;">Item</th>
                <th style="text-align: center; width: 15%; padding: 2px 0;">Qty</th>
                <th style="text-align: right; width: 22%; padding: 2px 0;">Price</th>
                <th style="text-align: right; width: 25%; padding: 2px 0;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td colspan="4" style="padding-top: 2px; font-weight: bold;">
                        {{ $line['display_name'] ?? $line['description'] }}
                    </td>
                </tr>
                <tr style="border-bottom: 1px dotted #ccc;">
                    <td style="color: #444; font-size: 9px;">
                        @if (! empty($line['item_code']))#{{ $line['item_code'] }}@endif
                    </td>
                    <td style="text-align: center; font-size: 10px;">{{ $line['quantity']['display'] }}</td>
                    <td style="text-align: right; font-size: 10px;">{{ $line['unit_price']['display'] }}</td>
                    <td style="text-align: right; font-weight: bold; font-size: 10px;">{{ $line['line_total']['display'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Divider -->
    <div style="border-top: 1px dashed #000; margin: 2mm 0;"></div>

    <!-- Totals -->
    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
        <tr>
            <td style="padding: 1px 0;">Subtotal:</td>
            <td style="text-align: right; padding: 1px 0;">{{ $amounts['subtotal']['display'] }}</td>
        </tr>
        @if (! $zeroMoney($amounts['discount_total']))
            <tr>
                <td style="padding: 1px 0;">Discount:</td>
                <td style="text-align: right; padding: 1px 0;">-{{ $amounts['discount_total']['display'] }}</td>
            </tr>
        @endif
        @if ($showTax && ! $zeroMoney($amounts['tax_total']))
            <tr>
                <td style="padding: 1px 0;">{{ $amounts['tax_total']['label'] }}:</td>
                <td style="text-align: right; padding: 1px 0;">{{ $amounts['tax_total']['display'] }}</td>
            </tr>
        @endif
        <tr style="font-size: 13px; font-weight: bold; border-top: 1px solid #000;">
            <td style="padding: 3px 0;">TOTAL:</td>
            <td style="text-align: right; padding: 3px 0;">{{ $amounts['grand_total']['display'] }}</td>
        </tr>
        @if (! $zeroMoney($amounts['paid_total']))
            <tr>
                <td style="padding: 1px 0;">Paid:</td>
                <td style="text-align: right; padding: 1px 0;">{{ $amounts['paid_total']['display'] }}</td>
            </tr>
            <tr style="font-weight: bold;">
                <td style="padding: 1px 0;">Balance Due:</td>
                <td style="text-align: right; padding: 1px 0;">{{ $amounts['balance_due']['display'] }}</td>
            </tr>
        @endif
    </table>

    <!-- Divider -->
    <div style="border-top: 1px dashed #000; margin: 2mm 0;"></div>

    @if ($showPayment)
        <div style="font-size: 9px; margin-bottom: 2mm;">
            <div>Payment: {{ $document['resolved_payment_mode'] ?? 'Cash' }}</div>
            @if (! empty($document['payment_terms']))
                <div>Terms: {{ $document['payment_terms'] }}</div>
            @endif
        </div>
    @endif

    @if (! empty($layout['footer_content']))
        <div style="text-align: center; font-size: 9px; margin: 2mm 0;">
            {{ $layout['footer_content'] }}
        </div>
    @else
        <div style="text-align: center; font-size: 9px; margin: 2mm 0;">
            Thank you for your business!
        </div>
    @endif

    @if (! empty($print_context))
        <div style="text-align: center; font-size: 8px; color: #555; margin-top: 2mm;">
            {{ $print_context['printed_at'] }} | {{ $print_context['printed_by'] }}
        </div>
    @endif
</div>
