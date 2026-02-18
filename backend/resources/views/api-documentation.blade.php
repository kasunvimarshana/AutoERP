<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoERP API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 120px;
            width: 280px;
            height: calc(100vh - 120px);
            background: white;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
            padding: 1.5rem;
        }
        
        .content {
            margin-left: 300px;
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .module-section {
            margin-bottom: 3rem;
        }
        
        .module-title {
            font-size: 1.8rem;
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .endpoint-card {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .http-method {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .method-get { background: #10b981; color: white; }
        .method-post { background: #3b82f6; color: white; }
        .method-put { background: #f59e0b; color: white; }
        .method-patch { background: #8b5cf6; color: white; }
        .method-delete { background: #ef4444; color: white; }
        
        .endpoint-path {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .auth-badge {
            background: #fbbf24;
            color: #92400e;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .endpoint-details {
            margin-top: 1rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            margin-top: 0.75rem;
            margin-bottom: 0.25rem;
        }
        
        .controller-info {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .params-list {
            list-style: none;
            margin-top: 0.5rem;
        }
        
        .param-item {
            padding: 0.5rem;
            background: white;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 3px solid #d1d5db;
        }
        
        .param-name {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #1f2937;
        }
        
        .param-type {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .required-badge {
            background: #ef4444;
            color: white;
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .nav-link {
            display: block;
            padding: 0.5rem 0;
            color: #4b5563;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .nav-link:hover {
            color: #667eea;
        }
        
        .nav-module {
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .export-buttons {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>AutoERP API Documentation</h1>
            <p>Enterprise-grade Multi-Tenant ERP System API Reference</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Generated: {{ $generated_at }}</p>
            <div class="export-buttons">
                <a href="/api/documentation/export/json" class="btn">Download JSON</a>
                <a href="/api/documentation/export/markdown" class="btn">Download Markdown</a>
            </div>
        </div>
    </div>
    
    <div class="sidebar">
        <div class="nav-module">Navigation</div>
        @foreach($modules as $moduleName => $endpoints)
            <a href="#module-{{ $moduleName }}" class="nav-link">{{ $moduleName }}</a>
        @endforeach
    </div>
    
    <div class="content">
        @foreach($modules as $moduleName => $endpoints)
            <div class="module-section" id="module-{{ $moduleName }}">
                <h2 class="module-title">{{ $moduleName }} Module</h2>
                
                @foreach($endpoints as $endpoint)
                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            @foreach($endpoint['http_methods'] as $method)
                                <span class="http-method method-{{ strtolower($method) }}">{{ $method }}</span>
                            @endforeach
                            <span class="endpoint-path">{{ $endpoint['endpoint'] }}</span>
                            @if($endpoint['requires_auth'])
                                <span class="auth-badge">🔒 Auth Required</span>
                            @endif
                        </div>
                        
                        <div class="endpoint-details">
                            @if($endpoint['description'])
                                <p>{{ $endpoint['description'] }}</p>
                            @endif
                            
                            <div class="detail-label">Controller Action:</div>
                            <div class="controller-info">
                                {{ $endpoint['controller'] }}@{{ $endpoint['action'] }}
                            </div>
                            
                            @if(!empty($endpoint['parameters']))
                                <div class="detail-label">Parameters:</div>
                                <ul class="params-list">
                                    @foreach($endpoint['parameters'] as $param)
                                        <li class="param-item">
                                            <span class="param-name">{{ $param['name'] }}</span>
                                            <span class="param-type">({{ $param['type'] }})</span>
                                            @if($param['required'])
                                                <span class="required-badge">Required</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            
                            @if($endpoint['route_name'])
                                <div class="detail-label">Route Name:</div>
                                <div class="controller-info">{{ $endpoint['route_name'] }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</body>
</html>
