@php
    $zeroMoney = static fn (array $amount): bool => bccomp((string) ($amount['raw'] ?? '0.000000'), '0', 6) === 0;
    $amounts = $document['amounts'];
    $lines = $document['lines'] ?? [];
    $settlementRowsVisible = ! $zeroMoney($amounts['paid_total'])
        || ! $zeroMoney($amounts['credit_total'])
        || bccomp((string) $amounts['balance_due']['raw'], (string) $amounts['grand_total']['raw'], 6) !== 0;
    $primaryColor = $layout['settings']['primary_color'] ?? '#173956';
    $showLogo = (bool) ($layout['settings']['show_company_logo'] ?? true);
    $showTax = (bool) ($layout['settings']['show_tax_summary'] ?? true);
    $showPayment = (bool) ($layout['settings']['show_payment_details'] ?? true);
    $showSignatures = (bool) ($layout['settings']['show_signatures'] ?? true);
    $showWords = (bool) ($layout['settings']['show_amount_in_words'] ?? true);
    $showNotes = (bool) ($layout['settings']['show_notes'] ?? true);
@endphp

<div class="modern-invoice">
    <!-- Header Banner -->
    <table class="modern-header" style="margin-bottom: 6mm;">
        <tr>
            <td style="vertical-align: top; width: 60%;">
                <div class="modern-brand" style="font-size: 22px; font-weight: 800; color: {{ $primaryColor }}; letter-spacing: 0.5px;">
                    {{ $document['supplier']['name'] }}
                </div>
                @if (! empty($document['supplier']['address']))
                    <div style="color: #4b5563; font-size: 11px; margin-top: 2px;">{{ $document['supplier']['address'] }}</div>
                @endif
                <div style="color: #6b7280; font-size: 10px; margin-top: 2px;">
                    @if (! empty($document['supplier']['phone'])) Tel: {{ $document['supplier']['phone'] }} @endif
                    @if (! empty($document['supplier']['tin'])) | TIN: {{ $document['supplier']['tin'] }} @endif
                    @if (! empty($document['supplier']['vat_registration_number'])) | VAT: {{ $document['supplier']['vat_registration_number'] }} @endif
                </div>
                @if (! empty($layout['header_content']))
                    <div class="modern-header-note" style="margin-top: 4px; font-size: 10px; color: #374151; font-style: italic;">
                        {{ $layout['header_content'] }}
                    </div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top; width: 40%;">
                <div class="modern-title" style="font-size: 20px; font-weight: 800; text-transform: uppercase; color: {{ $primaryColor }};">
                    {{ $document['title'] }}
                </div>
                <div style="font-size: 12px; font-weight: 700; color: #1f2937; margin-top: 3px;">
                    {{ $document['number_label'] }} <span style="font-family: monospace;">{{ $document['invoice_number'] }}</span>
                </div>
                <div style="font-size: 11px; color: #4b5563; margin-top: 2px;">
                    <strong>Date:</strong> {{ $document['invoice_date_display'] ?? $document['invoice_date'] }}
                </div>
                @if (! empty($document['due_date']))
                    <div style="font-size: 11px; color: #dc2626; margin-top: 2px;">
                        <strong>Due Date:</strong> {{ $document['due_date'] }}
                    </div>
                @endif
                <div style="margin-top: 4px;">
                    <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                        {{ $document['status'] }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Parties Section -->
    <table class="modern-parties" style="width: 100%; border-collapse: separate; border-spacing: 4mm 0; margin-bottom: 5mm;">
        <tr>
            <td style="width: 50%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $primaryColor }}; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px;">
                    Billed From
                </div>
                <div style="font-weight: 700; font-size: 12px; color: #0f172a;">{{ $document['supplier']['legal_name'] ?? $document['supplier']['name'] }}</div>
                @if (! empty($document['supplier']['address']))<div style="color: #475569; font-size: 10px; margin-top: 2px;">{{ $document['supplier']['address'] }}</div>@endif
                @if (! empty($document['supplier']['phone']))<div style="color: #475569; font-size: 10px;">Tel: {{ $document['supplier']['phone'] }}</div>@endif
                @if (! empty($document['supplier']['tin']))<div style="color: #475569; font-size: 10px;">TIN: {{ $document['supplier']['tin'] }}</div>@endif
                @if (! empty($document['supplier']['vat_registration_number']))<div style="color: #475569; font-size: 10px;">VAT Reg: {{ $document['supplier']['vat_registration_number'] }}</div>@endif
            </td>
            <td style="width: 50%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;">
                <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $primaryColor }}; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px;">
                    Billed To
                </div>
                <div style="font-weight: 700; font-size: 12px; color: #0f172a;">{{ $document['purchaser']['legal_name'] ?? $document['purchaser']['name'] }}</div>
                @if (! empty($document['purchaser']['address']))<div style="color: #475569; font-size: 10px; margin-top: 2px;">{{ $document['purchaser']['address'] }}</div>@endif
                @if (! empty($document['purchaser']['phone']))<div style="color: #475569; font-size: 10px;">Tel: {{ $document['purchaser']['phone'] }}</div>@endif
                @if (! empty($document['purchaser']['tin']))<div style="color: #475569; font-size: 10px;">TIN: {{ $document['purchaser']['tin'] }}</div>@endif
                @if (! empty($document['purchaser']['vat_registration_number']))<div style="color: #475569; font-size: 10px;">VAT Reg: {{ $document['purchaser']['vat_registration_number'] }}</div>@endif
            </td>
        </tr>
    </table>

    <!-- Reference Metadata if present -->
    @if (! empty($document['purchaser_reference_fields']))
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5mm; background: #f1f5f9; border-radius: 4px; padding: 4px;">
            <tr>
                @foreach (array_chunk($document['purchaser_reference_fields'], 4)[0] as $ref)
                    <td style="padding: 4px 8px; font-size: 10px; border-right: 1px solid #cbd5e1;">
                        <span style="color: #64748b; font-weight: 600; display: block; font-size: 9px;">{{ $ref['label'] }}</span>
                        <strong style="color: #1e293b;">{{ $ref['value'] }}</strong>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <!-- Line Items Table -->
    <table class="modern-lines" style="width: 100%; border-collapse: collapse; margin-top: 3mm; margin-bottom: 4mm;">
        <thead>
            <tr style="background: {{ $primaryColor }}; color: #ffffff;">
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; width: 6%;">#</th>
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: left; width: 44%;">Description of Goods or Services</th>
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: right; width: 10%;">Qty</th>
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: right; width: 14%;">Unit Price</th>
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: right; width: 12%;">Discount</th>
                <th style="padding: 6px 8px; font-size: 9px; text-transform: uppercase; text-align: right; width: 14%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr style="border-bottom: 1px solid #e2e8f0; background: {{ $loop->iteration % 2 === 0 ? '#f8fafc' : '#ffffff' }};">
                    <td style="padding: 6px 8px; font-size: 10px; color: #64748b; text-align: center;">{{ $loop->iteration }}</td>
                    <td style="padding: 6px 8px; font-size: 11px; color: #1e293b;">
                        <div style="font-weight: 600;">{{ $line['display_name'] ?? $line['description'] }}</div>
                        @if (! empty($line['item_code']))<span style="font-size: 9px; color: #64748b; font-family: monospace;">{{ $line['item_code'] }}</span>@endif
                        @if (! empty($line['item']) && $line['item'] !== ($line['display_name'] ?? ''))<div style="font-size: 9px; color: #64748b;">{{ $line['item'] }}</div>@endif
                    </td>
                    <td style="padding: 6px 8px; font-size: 11px; text-align: right; color: #1e293b; white-space: nowrap;">
                        {{ $line['quantity']['display'] }}
                        @if (! empty($line['uom']))<span style="font-size: 9px; color: #64748b;"> {{ $line['uom'] }}</span>@endif
                    </td>
                    <td style="padding: 6px 8px; font-size: 11px; text-align: right; color: #1e293b; white-space: nowrap;">{{ $line['unit_price']['display'] }}</td>
                    <td style="padding: 6px 8px; font-size: 11px; text-align: right; color: #dc2626; white-space: nowrap;">
                        @if (! $zeroMoney($line['discount_amount']))-{{ $line['discount_amount']['display'] }}@else-@endif
                    </td>
                    <td style="padding: 6px 8px; font-size: 11px; font-weight: 700; text-align: right; color: #0f172a; white-space: nowrap;">{{ $line['line_total']['display'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals & Payment Box -->
    <table style="width: 100%; border-collapse: separate; border-spacing: 4mm 0; margin-top: 2mm;">
        <tr>
            <td style="width: 50%; vertical-align: top; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px;">
                @if ($showPayment)
                    <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; color: {{ $primaryColor }}; margin-bottom: 4px;">Payment Information</div>
                    <div style="font-size: 10px; color: #334155; margin-bottom: 3px;"><strong>Method:</strong> {{ $document['resolved_payment_mode'] ?? 'Standard terms' }}</div>
                    @if (! empty($document['payment_terms']))
                        <div style="font-size: 10px; color: #334155; margin-bottom: 3px;"><strong>Terms:</strong> {{ $document['payment_terms'] }}</div>
                    @endif
                @endif
                @if ($showWords && ! empty($document['amount_in_words']))
                    <div style="margin-top: 4px; font-size: 10px; color: #475569;">
                        <strong>Amount in Words:</strong><br>
                        <em>{{ $document['amount_in_words'] }}</em>
                    </div>
                @endif
                @if ($showNotes && ! empty($document['notes']))
                    <div style="margin-top: 4px; font-size: 10px; color: #475569; border-top: 1px dotted #cbd5e1; padding-top: 4px;">
                        <strong>Notes:</strong> {{ $document['notes'] }}
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="padding: 3px 6px; color: #475569;">{{ $amounts['subtotal']['label'] }}</td>
                        <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #1e293b;">{{ $amounts['subtotal']['display'] }}</td>
                    </tr>
                    @if (! $zeroMoney($amounts['discount_total']))
                        <tr>
                            <td style="padding: 3px 6px; color: #dc2626;">Discounts</td>
                            <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #dc2626;">-{{ $amounts['discount_total']['display'] }}</td>
                        </tr>
                    @endif
                    @if ($showTax && ! $zeroMoney($amounts['tax_total']))
                        <tr>
                            <td style="padding: 3px 6px; color: #475569;">{{ $amounts['tax_total']['label'] }}</td>
                            <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #1e293b;">{{ $amounts['tax_total']['display'] }}</td>
                        </tr>
                    @endif
                    @if (! $zeroMoney($amounts['charge_total']))
                        <tr>
                            <td style="padding: 3px 6px; color: #475569;">Charges</td>
                            <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #1e293b;">{{ $amounts['charge_total']['display'] }}</td>
                        </tr>
                    @endif
                    @if (! $zeroMoney($amounts['adjustment_total']))
                        <tr>
                            <td style="padding: 3px 6px; color: #475569;">Other Adjustments</td>
                            <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #1e293b;">{{ $amounts['adjustment_total']['display'] }}</td>
                        </tr>
                    @endif
                    <tr style="border-top: 2px solid {{ $primaryColor }}; background: #f0fdf4;">
                        <td style="padding: 5px 6px; font-weight: 800; font-size: 12px; color: #166534;">Grand Total</td>
                        <td style="padding: 5px 6px; text-align: right; font-weight: 800; font-size: 13px; color: #166534;">{{ $amounts['grand_total']['display'] }}</td>
                    </tr>
                    @if ($settlementRowsVisible)
                        <tr>
                            <td style="padding: 3px 6px; color: #475569;">Paid</td>
                            <td style="padding: 3px 6px; text-align: right; font-weight: 600; color: #166534;">{{ $amounts['paid_total']['display'] }}</td>
                        </tr>
                        <tr style="border-top: 1px solid #cbd5e1; background: #fef2f2;">
                            <td style="padding: 4px 6px; font-weight: 700; color: #991b1b;">Balance Due</td>
                            <td style="padding: 4px 6px; text-align: right; font-weight: 800; font-size: 12px; color: #991b1b;">{{ $amounts['balance_due']['display'] }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Custom Footer Content / Policy -->
    @if (! empty($layout['footer_content']))
        <div style="margin-top: 6mm; text-align: center; font-size: 10px; color: #64748b; line-height: 1.4; border-top: 1px solid #e2e8f0; padding-top: 3mm;">
            {{ $layout['footer_content'] }}
        </div>
    @endif

    <!-- Signatures -->
    @if ($showSignatures)
        <table style="width: 100%; margin-top: 8mm; text-align: center; font-size: 10px; color: #475569;">
            <tr>
                <td style="width: 50%; padding: 0 10mm;">
                    <div style="border-top: 1px solid #94a3b8; padding-top: 4px;">Customer Acceptance</div>
                </td>
                <td style="width: 50%; padding: 0 10mm;">
                    <div style="border-top: 1px solid #94a3b8; padding-top: 4px;">Authorized Signatory</div>
                </td>
            </tr>
        </table>
    @endif
</div>
