#!/bin/bash

# Generate Swagger/OpenAPI Documentation
# This script properly scans all module controllers and generates the API documentation

echo "Generating Swagger/OpenAPI documentation..."

# Run the OpenAPI scanner with all controller directories
vendor/bin/openapi \
    app/Http/Controllers \
    Modules/Auth/app/Http/Controllers \
    Modules/User/app/Http/Controllers \
    Modules/Customer/app/Http/Controllers \
    --output storage/api-docs/api-docs.json

if [ $? -eq 0 ]; then
    echo "✓ Swagger documentation generated successfully!"
    echo "  Output: storage/api-docs/api-docs.json"
    echo ""
    echo "  Endpoint count:"
    cat storage/api-docs/api-docs.json | jq '.paths | keys | length'
else
    echo "✗ Failed to generate Swagger documentation"
    exit 1
fi
