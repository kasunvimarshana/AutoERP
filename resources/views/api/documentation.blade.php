<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - AutoERP</title>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        #swagger-ui {
            max-width: 1460px;
            margin: 0 auto;
        }
        .topbar {
            background-color: #1a202c;
        }
        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/api/v1/documentation/spec",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                defaultModelsExpandDepth: -1,
                docExpansion: "list",
                filter: true,
                persistAuthorization: true,
                displayRequestDuration: true,
                tryItOutEnabled: true,
                requestInterceptor: function(request) {
                    // Add X-Requested-With header for Laravel
                    request.headers['X-Requested-With'] = 'XMLHttpRequest';

                    // Add tenant ID from localStorage if available
                    const tenantId = localStorage.getItem('tenant_id');
                    if (tenantId) {
                        request.headers['X-Tenant-ID'] = tenantId;
                    }

                    return request;
                }
            });
        };
    </script>
</body>
</html>
