<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #4CAF50; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 32px; color: #333; }
        .order-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .order-table th, .order-table td { border: 1px solid #ddd; padding: 10px 14px; text-align: left; }
        .order-table th { background: #f0f0f0; font-weight: 600; }
        .total-row td { font-weight: bold; background: #f9f9f9; }
        .footer { background: #f0f0f0; padding: 16px 32px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✅ Order Confirmed!</h1>
    </div>
    <div class="body">
        <p>Hi {{ $recipientName ?? 'Customer' }},</p>
        <p>Your order <strong>#{{ $orderId }}</strong> has been confirmed and is being processed.</p>

        <h3>Order Summary</h3>
        <table class="order-table">
            <thead>
                <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach($items ?? [] as $item)
                <tr>
                    <td>{{ $item['name'] ?? $item['sku'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $currency ?? 'USD' }} {{ number_format($item['unit_price'], 2) }}</td>
                    <td>{{ $currency ?? 'USD' }} {{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Total</td>
                    <td>{{ $currency ?? 'USD' }} {{ number_format($totalAmount ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <p>You will receive a shipping notification once your order is dispatched.</p>
        <p>Thank you for your business!</p>
    </div>
    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
    </div>
</div>
</body>
</html>
