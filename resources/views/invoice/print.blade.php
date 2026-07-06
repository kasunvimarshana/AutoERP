<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tax Invoice</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color:#111; margin:0; padding:20px; }
        .container{ max-width:800px; margin:0 auto; border:1px solid #000; padding:12px; }
        .header{ text-align:center; border:2px solid #000; padding:8px; display:inline-block; margin-bottom:12px; }
        .controls{ display:flex; justify-content:flex-end; gap:8px; margin-bottom:8px }
        .btn{ background:#111; color:#fff; padding:6px 10px; border-radius:4px; text-decoration:none; font-size:13px }
        .row{ display:flex; gap:12px; margin-bottom:8px; }
        .col{ flex:1; }
        .box{ border:1px solid #000; padding:8px; min-height:80px; }
        .label{ font-weight:700; margin-bottom:6px; }
        table{ width:100%; border-collapse:collapse; margin-top:12px; }
        th, td{ border:1px solid #000; padding:6px; text-align:left; }
        th{ background:#f5f5f5; }
        .right{ text-align:right; }
        .totals td{ border:none; padding:8px; }
        .totals .labelcol{ width:80%; }
        @media print { body{ margin:0 } .container{ border:none } }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls no-print">
            <a href="#" id="printBtn" class="btn">Print</a>
            <a href="{{ route('invoice.pdf', ['id' => $invoice->id ?? 0]) }}" class="btn" target="_blank">Download PDF</a>
        </div>
        <div style="text-align:center; font-size:12px; color:#666;">GOVERNMENT OF SRI LANKA - SAMPLE</div>
        <div class="header">Tax Invoice</div>

        <div class="row">
            <div style="flex:1;">
                <div style="display:flex; gap:8px;">
                    <div style="flex:1;">
                        <div class="label">Date of Invoice:</div>
                        <div>{{ optional($invoice)->invoice_date ?? now()->toDateString() }}</div>
                    </div>
                    <div style="flex:1;">
                        <div class="label">Tax Invoice No.:</div>
                        <div>{{ optional($invoice)->invoice_number ?? '---' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col box">
                <div class="label">Supplier's TIN:<br>Supplier's Name:<br>Address:</div>
                <div>
                    {{ optional($invoice->party)->tin ?? '' }}<br>
                    {{ optional($invoice->party)->name ?? optional($invoice)->party_name ?? 'Supplier' }}<br>
                    {{ optional($invoice->party)->address ?? '' }}
                </div>
                <div style="margin-top:6px;">Telephone No: {{ optional($invoice->party)->phone ?? '' }}</div>
            </div>
            <div class="col box">
                <div class="label">Purchaser's TIN:<br>Purchaser's Name:<br>Address:</div>
                <div>
                    {{ optional($invoice->customer)->tin ?? '' }}<br>
                    {{ optional($invoice->customer)->name ?? optional($invoice)->customer_name ?? 'Purchaser' }}<br>
                    {{ optional($invoice->customer)->address ?? '' }}
                </div>
                <div style="margin-top:6px;">Telephone No: {{ optional($invoice->customer)->phone ?? '' }}</div>
            </div>
        </div>

        <div class="row">
            <div style="flex:1;">
                <div class="label">Date of Delivery:</div>
                <div>{{ optional($invoice)->delivery_date ?? '' }}</div>
            </div>
            <div style="flex:1;">
                <div class="label">Place of Supply:</div>
                <div>{{ optional($invoice)->place_of_supply ?? '' }}</div>
            </div>
        </div>

        <div style="margin-top:8px; border:1px solid #000; padding:8px;">Additional Information if any: {{ optional($invoice)->notes ?? '' }}</div>

        <table>
            <thead>
                <tr>
                    <th style="width:8%">Reference</th>
                    <th style="width:52%">Description of Goods or Services</th>
                    <th style="width:10%">Quantity</th>
                    <th style="width:15%">Unit Price</th>
                    <th style="width:15%">Amount<br>Excluding VAT (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines ?? collect() as $line)
                <tr>
                    <td>{{ $line->reference ?? '' }}</td>
                    <td>{{ $line->description ?? $line->name ?? '' }}</td>
                    <td class="right">{{ $line->quantity ?? '' }}</td>
                    <td class="right">{{ number_format($line->unit_price ?? $line->price ?? 0, 2) }}</td>
                    <td class="right">{{ number_format(($line->unit_price ?? $line->price ?? 0) * ($line->quantity ?? 1), 2) }}</td>
                </tr>
                @endforeach
                @for($i = ($invoice->lines->count() ?? 0); $i < 6; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <table class="totals" style="margin-top:12px; width:100%">
            <tr>
                <td class="labelcol"></td>
                <td style="text-align:right; width:20%">Total Value of Supply:</td>
                <td style="text-align:right; width:20%">{{ number_format($invoice->lines->sum(function($l){ return ($l->unit_price ?? $l->price ?? 0) * ($l->quantity ?? 1); }), 2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:right">VAT Amount (Total Value of Supply @ 18%):</td>
                <td style="text-align:right">{{ number_format(($invoice->lines->sum(function($l){ return ($l->unit_price ?? $l->price ?? 0) * ($l->quantity ?? 1); }) * 0.18), 2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:right">Total Amount including VAT:</td>
                <td style="text-align:right">{{ number_format($invoice->lines->sum(function($l){ return ($l->unit_price ?? $l->price ?? 0) * ($l->quantity ?? 1); }) * 1.18, 2) }}</td>
            </tr>
        </table>

        <div style="margin-top:12px; border:1px solid #000; padding:8px;">
            <div>Total Amount in words: {{ optional($invoice)->amount_in_words ?? '' }}</div>
            <div>Mode of Payment: {{ optional($invoice)->payment_mode ?? '' }}</div>
        </div>

        <div style="margin-top:18px; font-size:11px; color:#666">EOG 11 - 0124</div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var b = document.getElementById('printBtn');
            if(b) b.addEventListener('click', function(e){ e.preventDefault(); window.print(); });
        });
    </script>
    <style>
        @media print { .no-print{ display:none !important } .container{ border:none; width:100%; margin:0; padding:0 } }
    </style>
</body>
</html>
