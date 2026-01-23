<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ModularSaaS - Vehicle Service Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #fff; }
        .container { text-align: center; padding: 2rem; }
        h1 { font-size: 3rem; margin-bottom: 1rem; font-weight: 700; }
        p { font-size: 1.25rem; opacity: 0.9; margin-bottom: 2rem; }
        .badge { display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border-radius: 50px; margin: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>ModularSaaS</h1>
        <p>Enterprise Vehicle Service Center Platform</p>
        <div>
            <span class="badge">Clean Architecture</span>
            <span class="badge">Multi-Tenant</span>
            <span class="badge">Laravel {{ app()->version() }}</span>
        </div>
    </div>
</body>
</html>
