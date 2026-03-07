<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #f44336; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 24px; }
        .body { padding: 32px; color: #333; }
        .reason-box { background: #fff3f3; border-left: 4px solid #f44336; padding: 12px 16px; margin: 16px 0; }
        .footer { background: #f0f0f0; padding: 16px 32px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>❌ Order Cancelled</h1>
    </div>
    <div class="body">
        <p>Hi {{ $recipientName ?? 'Customer' }},</p>
        <p>We regret to inform you that your order <strong>#{{ $orderId }}</strong> has been cancelled.</p>

        @if(!empty($reason))
        <div class="reason-box">
            <strong>Reason:</strong> {{ $reason }}
        </div>
        @endif

        <p>If a payment was made, a full refund will be processed within 3-5 business days.</p>
        <p>If you believe this was a mistake, please contact our support team.</p>
        <p>We apologize for any inconvenience.</p>
    </div>
    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
    </div>
</div>
</body>
</html>
