<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $voucher['voucher_number'] ?? 'Voucher' }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; font-size: 12px; margin: 32px; }
        h1 { font-size: 24px; margin: 0 0 6px; }
        h2 { border-bottom: 1px solid #d1d5db; font-size: 14px; margin: 22px 0 8px; padding-bottom: 4px; }
        table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .meta { display: grid; gap: 6px 24px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 18px; }
        .label { color: #6b7280; display: block; font-size: 10px; text-transform: uppercase; }
        .amount { font-size: 20px; font-weight: 700; text-align: right; }
        .signatures { display: grid; gap: 32px; grid-template-columns: repeat(4, 1fr); margin-top: 48px; }
        .signature { border-top: 1px solid #111827; padding-top: 8px; text-align: center; }
        @media print { body { margin: 18mm; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <h1>{{ $voucher['voucher_label'] ?? 'Voucher' }}</h1>
    <div>{{ $voucher['voucher_number'] ?? '' }}</div>

    <div class="meta">
        <div><span class="label">Date</span>{{ $voucher['voucher_date'] ?? '' }}</div>
        <div><span class="label">Source</span>{{ $voucher['source_module'] ?? '' }} / {{ $voucher['source_document_number'] ?? '' }}</div>
        <div><span class="label">Status</span>{{ $voucher['document_status'] ?? '' }} / {{ $voucher['posting_status'] ?? '' }}</div>
        <div><span class="label">Party</span>{{ $voucher['payer_or_payee'] ?? $voucher['party_name'] ?? '' }}</div>
        <div><span class="label">Currency</span>{{ $voucher['currency'] ?? '' }} @ {{ $voucher['exchange_rate'] ?? '' }}</div>
        <div class="amount">{{ $voucher['transaction_amount'] ?? '' }}</div>
    </div>

    <h2>Narration</h2>
    <p>{{ $voucher['narration'] ?? '' }}</p>

    @if (! empty($voucher['invoice_or_payable_references']))
        <h2>Allocations</h2>
        <table>
            <thead><tr><th>Document</th><th>Date</th><th>Allocated</th><th>Balance After</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($voucher['invoice_or_payable_references'] as $line)
                <tr>
                    <td>{{ $line['invoice_number'] ?? '' }}</td>
                    <td>{{ $line['allocation_date'] ?? '' }}</td>
                    <td>{{ $line['allocated_amount'] ?? '' }}</td>
                    <td>{{ $line['invoice_balance_after'] ?? '' }}</td>
                    <td>{{ $line['status'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($voucher['payment_lines']))
        <h2>Payment Instruments</h2>
        <table>
            <thead><tr><th>Method</th><th>Instrument</th><th>Bank</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($voucher['payment_lines'] as $line)
                <tr>
                    <td>{{ $line['method'] ?? $line['method_type'] ?? '' }}</td>
                    <td>{{ $line['instrument_number'] ?? $line['reference_number'] ?? '' }}</td>
                    <td>{{ $line['external_bank'] ?? $line['internal_bank_account'] ?? '' }}</td>
                    <td>{{ $line['amount'] ?? '' }}</td>
                    <td>{{ $line['status'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($voucher['journal_lines']))
        <h2>Journal Lines</h2>
        <table>
            <thead><tr><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
            <tbody>
            @foreach ($voucher['journal_lines'] as $line)
                <tr>
                    <td>{{ $line['account_code'] ?? '' }} {{ $line['account_name'] ?? '' }}</td>
                    <td>{{ $line['description'] ?? '' }}</td>
                    <td>{{ $line['debit'] ?? '' }}</td>
                    <td>{{ $line['credit'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="signatures">
        <div class="signature">Prepared By</div>
        <div class="signature">Checked By</div>
        <div class="signature">Approved By</div>
        <div class="signature">Received By</div>
    </div>
    <p>Printed at {{ now()->toDateTimeString() }}</p>
</body>
</html>
