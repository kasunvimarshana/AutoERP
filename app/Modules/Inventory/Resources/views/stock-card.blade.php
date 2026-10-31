<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #172033; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        .details { margin-bottom: 12px; }
        .details span { margin-right: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d9e1ec; padding: 5px; text-align: left; }
        th { background: #eaf0f8; }
        .number { text-align: right; white-space: nowrap; }
        tfoot { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Stock Card</h1>
    <div class="details">
        <span>Item: {{ $card['item']['code'] }} - {{ $card['item']['name'] }}</span>
        <span>Warehouse: {{ $card['warehouse']['name'] }}</span>
        <span>Location: {{ $card['location']['name'] ?? 'All locations' }}</span>
        <span>Period: {{ $card['date_from'] }} to {{ $card['date_to'] }}</span>
        <span>UOM: {{ $card['item']['uom'] }}</span>
    </div>
    <table>
        <thead><tr><th>Posted at</th><th>Document date</th><th>Document</th><th>Description</th><th class="number">IN</th><th class="number">OUT</th><th class="number">Balance</th></tr></thead>
        <tbody>
            <tr><td colspan="6">Opening balance</td><td class="number">{{ $card['opening'] }}</td></tr>
            @foreach ($card['rows'] as $row)
                <tr>
                    <td>{{ $row['posted_at'] }}</td><td>{{ $row['document_date'] }}</td>
                    <td>{{ $row['document']['label'] ?? $row['movement_number'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="number">{{ $row['quantity_in'] }}</td><td class="number">{{ $row['quantity_out'] }}</td><td class="number">{{ $row['balance'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="4">Period totals / closing balance</td><td class="number">{{ $card['total_in'] }}</td><td class="number">{{ $card['total_out'] }}</td><td class="number">{{ $card['closing'] }}</td></tr></tfoot>
    </table>
</body>
</html>
