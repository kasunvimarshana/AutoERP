{{--
AutoERP Enterprise System

@author Kasun Vimarshana <kasunvmail@gmail.com>

Phone: +94713525253
--}}
<!doctype html>
<html lang="en" class="mode-{{ $mode ?? 'html' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cheque Print - {{ $payment['payment_number'] ?? '' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: {{ (float) ($template['page_width_mm'] ?? 210) }}mm {{ (float) ($template['page_height_mm'] ?? 99) }}mm;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #000;
            font-family: {{ $template['font_family'] ?: 'Arial, sans-serif' }};
            font-size: {{ (float) ($template['font_size'] ?? 12) }}pt;
            line-height: 1.2;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .screen-toolbar {
            max-width: 860px;
            margin: 16px auto;
            padding: 12px 20px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        }

        .screen-toolbar .info {
            font-size: 13px;
            color: #334155;
        }

        .screen-toolbar .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .cheque-viewport {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }

        .cheque-canvas {
            position: relative;
            width: {{ (float) ($template['page_width_mm'] ?? 210) }}mm;
            height: {{ (float) ($template['page_height_mm'] ?? 99) }}mm;
            background: #ffffff;
            overflow: hidden;
            border: 1px solid #94a3b8;
            box-shadow: 0 10px 25px rgb(15 23 42 / 0.15);
        }

        .field {
            position: absolute;
            white-space: nowrap;
        }

        .crossing-container {
            position: absolute;
            width: 32mm;
            height: 18mm;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            transform: rotate(-12deg);
        }

        .bearer-strike {
            position: absolute;
            height: 1.5px;
            background: #000;
            width: 25mm;
        }

        .date-boxed {
            display: flex;
            gap: 1.5mm;
        }

        .date-box-digit {
            display: inline-block;
            width: 4.5mm;
            text-align: center;
        }

        @media print {
            .screen-toolbar {
                display: none !important;
            }

            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .cheque-viewport {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .cheque-canvas {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    @if (($mode ?? 'html') !== 'pdf')
        <div class="screen-toolbar">
            <div class="info">
                <strong>{{ $payment['payment_number'] ?? 'Cheque' }}</strong> &bull;
                Payee: {{ $line['payee_name'] ?? '' }} &bull;
                Amount: {{ $line['formatted_amount'] ?? $line['amount'] ?? '' }} &bull;
                Template: {{ $template['template_name'] ?? '' }}
            </div>
            <div class="actions">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print Cheque</button>
                <a href="?format=pdf" class="btn btn-secondary">Download PDF</a>
            </div>
        </div>
    @endif

    <div class="cheque-viewport">
        <div class="cheque-canvas">
            {{-- Crossing (A/C Payee Only / Not Negotiable) --}}
            @if (($line['crossing_type'] ?? '') !== 'none' && !empty($line['crossing_text']))
                @php
                    $crossX = (float) ($template['metadata']['crossing_x_mm'] ?? 15);
                    $crossY = (float) ($template['metadata']['crossing_y_mm'] ?? 10);
                @endphp
                <div class="crossing-container" style="left: {{ $crossX }}mm; top: {{ $crossY }}mm;">
                    {{ $line['crossing_text'] }}
                </div>
            @endif

            {{-- Cheque Date --}}
            @if (!empty($template['date_x_mm']) && !empty($template['date_y_mm']))
                @php
                    $dateLetterSpacing = (float) ($template['metadata']['date_letter_spacing'] ?? 0);
                @endphp
                <div class="field" style="left: {{ (float) $template['date_x_mm'] }}mm; top: {{ (float) $template['date_y_mm'] }}mm;">
                    @if ($dateLetterSpacing > 0 && !empty($line['date_digits']))
                        <div class="date-boxed" style="gap: {{ $dateLetterSpacing }}mm;">
                            @foreach ($line['date_digits'] as $digit)
                                <span class="date-box-digit">{{ $digit }}</span>
                            @endforeach
                        </div>
                    @else
                        <span>{{ $line['formatted_cheque_date'] ?? $line['cheque_date'] ?? '' }}</span>
                    @endif
                </div>
            @endif

            {{-- Payee Name --}}
            @if (!empty($template['payee_x_mm']) && !empty($template['payee_y_mm']))
                <div class="field" style="left: {{ (float) $template['payee_x_mm'] }}mm; top: {{ (float) $template['payee_y_mm'] }}mm; font-weight: 600;">
                    {{ $line['payee_name'] ?? '' }}
                </div>
            @endif

            {{-- Bearer Strike --}}
            @if (!empty($line['show_bearer_strike']))
                @php
                    $bearerX = (float) ($template['metadata']['bearer_x_mm'] ?? ($template['payee_x_mm'] + 130));
                    $bearerY = (float) ($template['metadata']['bearer_y_mm'] ?? ($template['payee_y_mm'] + 2));
                @endphp
                <div class="bearer-strike" style="left: {{ $bearerX }}mm; top: {{ $bearerY }}mm;"></div>
            @endif

            {{-- Numeric Amount in Figures --}}
            @if (!empty($template['amount_x_mm']) && !empty($template['amount_y_mm']))
                <div class="field" style="left: {{ (float) $template['amount_x_mm'] }}mm; top: {{ (float) $template['amount_y_mm'] }}mm; font-weight: 700;">
                    {{ $line['formatted_amount'] ?? $line['amount'] ?? '' }}
                </div>
            @endif

            {{-- Amount in Words (Line 1) --}}
            @if (!empty($template['amount_words_x_mm']) && !empty($template['amount_words_y_mm']))
                @php
                    $maxWidthMm = (float) ($template['page_width_mm'] - $template['amount_words_x_mm'] - 8);
                @endphp
                <div class="field" style="left: {{ (float) $template['amount_words_x_mm'] }}mm; top: {{ (float) $template['amount_words_y_mm'] }}mm; max-width: {{ $maxWidthMm }}mm; white-space: normal;">
                    {{ $line['amount_words_line_1'] ?? $line['amount_in_words'] ?? '' }}
                </div>
            @endif

            {{-- Amount in Words (Line 2 if separated) --}}
            @if (!empty($line['amount_words_line_2']))
                @php
                    $line2X = (float) ($template['metadata']['amount_words_line_2_x_mm'] ?? $template['amount_words_x_mm']);
                    $line2Y = (float) ($template['metadata']['amount_words_line_2_y_mm'] ?? ($template['amount_words_y_mm'] + 7));
                    $maxWidthMm = (float) ($template['page_width_mm'] - $line2X - 8);
                @endphp
                <div class="field" style="left: {{ $line2X }}mm; top: {{ $line2Y }}mm; max-width: {{ $maxWidthMm }}mm; white-space: normal;">
                    {{ $line['amount_words_line_2'] }}
                </div>
            @endif

            {{-- Cheque Instrument Number (if configured) --}}
            @if (!empty($template['cheque_number_x_mm']) && !empty($template['cheque_number_y_mm']) && !empty($line['cheque_number']))
                <div class="field" style="left: {{ (float) $template['cheque_number_x_mm'] }}mm; top: {{ (float) $template['cheque_number_y_mm'] }}mm;">
                    {{ $line['cheque_number'] }}
                </div>
            @endif

            {{-- Memo / Payment Reference --}}
            @if (!empty($template['metadata']['memo_x_mm']) && !empty($template['metadata']['memo_y_mm']))
                <div class="field" style="left: {{ (float) $template['metadata']['memo_x_mm'] }}mm; top: {{ (float) $template['metadata']['memo_y_mm'] }}mm; font-size: {{ max(8, (float) $template['font_size'] - 3) }}pt;">
                    {{ $line['memo_text'] ?? '' }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
