<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invoice not found</title>
    <style>body{font-family:Arial,Helvetica,sans-serif;padding:24px;color:#222} .box{border:1px solid #ddd;padding:20px;border-radius:6px;max-width:720px;margin:32px auto}</style>
</head>
<body>
    <div class="box">
        <h2>Invoice not found</h2>
        <p>The invoice with id <strong>{{ $id }}</strong> was not found.</p>
        <p>Please verify the invoice ID or go back to the <a href="/invoices">Invoices list</a>.</p>
    </div>
</body>
</html>
