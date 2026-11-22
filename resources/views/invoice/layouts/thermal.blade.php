@php
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];

    $settings = $layout['settings'] ?? [];
    $showTax = (bool) ($settings['show_tax_summary'] ?? $settings['show_tax_breakdown'] ?? true);
    $showPayment = (bool) ($settings['show_payment_details'] ?? $settings['show_payment_stub'] ?? true);
    $showSignatures = (bool) ($settings['show_signatures'] ?? false);
    $showNotes = (bool) ($settings['show_notes'] ?? true);
    $customNotes = ! empty($settings['notes']) ? $settings['notes'] : ($document['notes'] ?? null);
    $customTerms = ! empty($settings['terms']) ? $settings['terms'] : ($document['payment_terms'] ?? null);
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
        @if (! empty($layout['header_content']))
            <div style="font-size: 9px; font-style: italic; margin-top: 2px;">{{ $layout['header_content'] }}</div>
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
        @if (! empty($document['purchaser_reference_fields']))
            <div style="margin-top: 2px; border-top: 1px dotted #ccc; padding-top: 2px;">
                @foreach ($document['purchaser_reference_fields'] as $ref)
                    <div style="display: flex; justify-content: space-between; font-size: 9px; color: #333;">
                        <span>{{ $ref['label'] }}:</span>
                        <span>{{ $ref['value'] }}</span>
                    </div>
                @endforeach
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
            <td style="padding: 2px 0;">TOTAL:</td>
            <td style="text-align: right; padding: 2px 0;">{{ $amounts['grand_total']['display'] }}</td>
        </tr>
        @if (! $zeroMoney($amounts['paid_total']))
            <tr>
                <td style="padding: 1px 0;">Paid:</td>
                <td style="text-align: right; padding: 1px 0;">{{ $amounts['paid_total']['display'] }}</td>
            </tr>
        @endif
        @if (! $zeroMoney($amounts['balance_due']))
            <tr style="font-weight: bold; color: #b91c1c;">
                <td style="padding: 1px 0;">Balance Due:</td>
                <td style="text-align: right; padding: 1px 0;">{{ $amounts['balance_due']['display'] }}</td>
            </tr>
        @endif
    </table>

    @if ($showPayment)
        <!-- Divider -->
        <div style="border-top: 1px dashed #000; margin: 2mm 0;"></div>

        <div style="font-size: 10px;">
            <div>Payment: {{ $document['resolved_payment_mode'] ?? 'Cash' }}</div>
            @if (! empty($customTerms))
                <div>Terms: {{ $customTerms }}</div>
            @endif
        </div>
    @endif

    @if ($showNotes && ! empty($customNotes))
        <div style="margin-top: 2mm; font-size: 9px; text-align: center; color: #333;">
            {{ $customNotes }}
        </div>
    @endif

    <!-- Footer Policy -->
    <div style="text-align: center; margin-top: 3mm; font-size: 9px; line-height: 1.3;">
        @if (! empty($layout['footer_content']))
            <div>{{ $layout['footer_content'] }}</div>
        @else
            <div>Thank you for your business!</div>
            <div>Exchange possible within 7 days with receipt.</div>
        @endif
    </div>

    @if ($showSignatures)
        <div style="margin-top: 6mm; text-align: center; font-size: 9px;">
            <div style="border-top: 1px dotted #000; width: 60%; margin: 0 auto; padding-top: 1mm;">
                Customer Signature
            </div>
        </div>
    @endif

    <div style="text-align: center; margin-top: 2mm; font-size: 8px; color: #666;">
        Printed: {{ $print_context['printed_at'] ?? now()->format('Y-m-d H:i') }}
    </div>
</div>
