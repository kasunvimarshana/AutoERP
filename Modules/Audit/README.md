# Audit Module REST API

Comprehensive read-only REST API for accessing, filtering, and exporting audit logs with statistics and reporting capabilities.

## Overview

The Audit API provides secure, tenant-scoped access to audit logs with:
- Comprehensive filtering and search capabilities
- Statistics and analytics
- CSV/JSON export functionality
- Production-ready performance optimization
- Policy-based authorization

## Authentication & Authorization

All endpoints require:
- **Authentication**: Valid JWT token via `Authorization: Bearer {token}`
- **Tenant Context**: Automatic tenant scoping via middleware
- **Permissions**: Policy-based access control
  - `audit.view` - View audit logs and statistics
  - `audit.export` - Export audit logs

## Endpoints

### 1. List Audit Logs

**GET** `/api/v1/audit-logs`

Retrieve paginated audit logs with comprehensive filtering.

#### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `event` | string | Filter by event type | `created`, `updated`, `deleted` |
| `auditable_type` | string | Filter by model type | `Product`, `User` |
| `auditable_id` | uuid | Filter by specific record | `550e8400-e29b-41d4-a716-446655440000` |
| `user_id` | uuid | Filter by user who performed action | `550e8400-e29b-41d4-a716-446655440001` |
| `organization_id` | uuid | Filter by organization | `550e8400-e29b-41d4-a716-446655440002` |
| `ip_address` | string | Filter by IP address | `192.168.1.1` |
| `from_date` | date | Start date (inclusive) | `2024-01-01` |
| `to_date` | date | End date (inclusive) | `2024-12-31` |
| `search` | string | Search in JSON fields and text | `price` |
| `per_page` | integer | Items per page (1-100, default: 15) | `25` |
| `page` | integer | Page number (default: 1) | `2` |
| `sort_by` | string | Sort field | `created_at`, `event`, `auditable_type`, `user_id` |
| `sort_order` | string | Sort direction | `asc`, `desc` (default) |

#### Response

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "event": "updated",
      "auditable_type": "Modules\\Product\\Models\\Product",
      "auditable_id": "550e8400-e29b-41d4-a716-446655440003",
      "auditable": {
        "type": "Modules\\Product\\Models\\Product",
        "id": "550e8400-e29b-41d4-a716-446655440003",
        "data": {...}
      },
      "user": {
        "id": "550e8400-e29b-41d4-a716-446655440001",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "user_id": "550e8400-e29b-41d4-a716-446655440001",
      "organization_id": "550e8400-e29b-41d4-a716-446655440002",
      "old_values": {
        "price": "100.00",
        "status": "active"
      },
      "new_values": {
        "price": "150.00",
        "status": "active"
      },
      "changes": {
        "price": {
          "from": "100.00",
          "to": "150.00"
        }
      },
      "metadata": {
        "reason": "Price update",
        "batch_id": "batch_123"
      },
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2024-01-15T10:30:00Z",
      "created_at_human": "2 hours ago"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

#### Examples

```bash
# Get all audit logs for the last 7 days
curl -X GET "https://api.example.com/api/v1/audit-logs?from_date=2024-01-08&to_date=2024-01-15" \
  -H "Authorization: Bearer {token}"

# Get product updates by specific user
curl -X GET "https://api.example.com/api/v1/audit-logs?event=updated&auditable_type=Product&user_id=550e8400-e29b-41d4-a716-446655440001" \
  -H "Authorization: Bearer {token}"

# Search for price changes
curl -X GET "https://api.example.com/api/v1/audit-logs?search=price&per_page=50" \
  -H "Authorization: Bearer {token}"
```

### 2. Get Audit Log Details

**GET** `/api/v1/audit-logs/{auditLog}`

Retrieve detailed information for a specific audit log.

#### Response

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "event": "updated",
    "auditable_type": "Modules\\Product\\Models\\Product",
    "auditable_id": "550e8400-e29b-41d4-a716-446655440003",
    "auditable": {
      "type": "Modules\\Product\\Models\\Product",
      "id": "550e8400-e29b-41d4-a716-446655440003",
      "data": {
        "id": "550e8400-e29b-41d4-a716-446655440003",
        "name": "Sample Product",
        "price": "150.00"
      }
    },
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "John Doe",
      "email": "john@example.com"
    },
    "old_values": {...},
    "new_values": {...},
    "changes": {...},
    "metadata": {...},
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2024-01-15T10:30:00Z",
    "created_at_human": "2 hours ago"
  }
}
```

### 3. Get Audit Statistics

**GET** `/api/v1/audit-logs/statistics`

Get comprehensive statistics and analytics about audit logs.

#### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `from_date` | date | Start date for statistics | `2024-01-01` |
| `to_date` | date | End date for statistics | `2024-01-31` |
| `group_by` | string | Timeline grouping | `hour`, `day` (default), `month` |

#### Response

```json
{
  "data": {
    "total_logs": 15432,
    "by_event": {
      "created": 5234,
      "updated": 8456,
      "deleted": 1742
    },
    "by_auditable_type": {
      "Modules\\Product\\Models\\Product": 6789,
      "Modules\\Auth\\Models\\User": 4123,
      "Modules\\Inventory\\Models\\StockMovement": 3520,
      "Modules\\Sales\\Models\\Order": 1000
    },
    "by_user": [
      {
        "user_id": "550e8400-e29b-41d4-a716-446655440001",
        "user_name": "John Doe",
        "user_email": "john@example.com",
        "count": 3245
      },
      {
        "user_id": "550e8400-e29b-41d4-a716-446655440002",
        "user_name": "Jane Smith",
        "user_email": "jane@example.com",
        "count": 2876
      }
    ],
    "timeline": {
      "2024-01-15": 523,
      "2024-01-14": 487,
      "2024-01-13": 612,
      "2024-01-12": 398
    },
    "recent_activity": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "event": "updated",
        "auditable_type": "Modules\\Product\\Models\\Product",
        "user_name": "John Doe",
        "created_at": "2024-01-15T10:30:00Z",
        "created_at_human": "5 minutes ago"
      }
    ]
  }
}
```

#### Examples

```bash
# Get statistics for the last 30 days
curl -X GET "https://api.example.com/api/v1/audit-logs/statistics?from_date=2023-12-16&to_date=2024-01-15" \
  -H "Authorization: Bearer {token}"

# Get hourly activity timeline
curl -X GET "https://api.example.com/api/v1/audit-logs/statistics?group_by=hour" \
  -H "Authorization: Bearer {token}"
```

### 4. Export Audit Logs

**GET** `/api/v1/audit-logs/export`

Export filtered audit logs to CSV or JSON format.

#### Query Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `format` | string | Yes | Export format | `csv`, `json` |
| `columns` | array | No | Columns to export | `id,event,auditable_type,created_at` |
| `from_date` | date | No | Start date filter | `2024-01-01` |
| `to_date` | date | No | End date filter | `2024-01-31` |
| `event` | string | No | Filter by event type | `updated` |
| `auditable_type` | string | No | Filter by model type | `Product` |

#### Available Columns

- `id` - Audit log UUID
- `event` - Event type
- `auditable_type` - Model class name
- `auditable_id` - Model UUID
- `user_id` - User who performed action (includes name in export)
- `organization_id` - Organization UUID
- `ip_address` - IP address
- `created_at` - Timestamp

#### Response

**CSV Format:**
```csv
Id,Event,Auditable_type,Auditable_id,User_id,Ip_address,Created_at
550e8400-e29b-41d4-a716-446655440000,updated,Modules\Product\Models\Product,550e8400-e29b-41d4-a716-446655440003,John Doe,192.168.1.1,2024-01-15 10:30:00
```

**JSON Format:**
```json
[
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "event": "updated",
    "auditable_type": "Modules\\Product\\Models\\Product",
    "auditable_id": "550e8400-e29b-41d4-a716-446655440003",
    "user_id": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "John Doe"
    },
    "ip_address": "192.168.1.1",
    "created_at": "2024-01-15T10:30:00Z"
  }
]
```

#### Examples

```bash
# Export all product updates to CSV
curl -X GET "https://api.example.com/api/v1/audit-logs/export?format=csv&auditable_type=Product&event=updated" \
  -H "Authorization: Bearer {token}" \
  --output audit_logs.csv

# Export last month's logs to JSON with specific columns
curl -X GET "https://api.example.com/api/v1/audit-logs/export?format=json&from_date=2023-12-01&to_date=2023-12-31&columns[]=id&columns[]=event&columns[]=created_at" \
  -H "Authorization: Bearer {token}" \
  --output audit_logs.json
```

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Audit log not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["The per page must not be greater than 100."],
    "to_date": ["The end date must be equal to or after the start date."]
  }
}
```

## Performance Considerations

### Indexed Queries
The following fields are indexed for optimal query performance:
- `tenant_id`
- `user_id`
- `event`
- `auditable_type`
- `auditable_id`
- `created_at`

### Large Dataset Handling
- Export operations use chunking (1000 records per chunk) to handle large datasets efficiently
- Statistics queries are optimized with database-level aggregations
- Pagination is enforced (max 100 items per page)

### Caching Recommendations
Consider caching:
- Statistics data (5-15 minute TTL)
- Frequently accessed audit log filters

## Security

### Tenant Isolation
- All queries are automatically scoped to the authenticated user's tenant
- Cross-tenant access is prevented at the database query level

### Authorization
- Policy-based access control via `AuditLogPolicy`
- Permission checks: `audit.view`, `audit.export`
- Individual log access validates tenant ownership

### Data Protection
- Read-only operations (no create/update/delete)
- Sensitive data in `user_agent` can be filtered if needed
- IP addresses are logged but can be anonymized per compliance requirements

## Use Cases

### Compliance & Auditing
- Track all changes to critical business data
- Generate audit reports for compliance requirements
- Investigate specific user actions or data modifications

### Security Monitoring
- Monitor suspicious activities by IP address
- Track failed login attempts or unauthorized access
- Identify unusual patterns in user behavior

### Business Intelligence
- Analyze user activity patterns
- Track most frequently modified data
- Measure system usage over time

### Troubleshooting
- Debug data inconsistencies
- Track down when and how data was changed
- Identify root causes of issues

## Integration Example

### JavaScript/TypeScript

```typescript
import axios from 'axios';

const auditClient = axios.create({
  baseURL: 'https://api.example.com/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
});

// Get audit logs
const getAuditLogs = async (filters) => {
  const response = await auditClient.get('/audit-logs', {
    params: filters,
  });
  return response.data;
};

// Get statistics
const getStatistics = async (dateRange) => {
  const response = await auditClient.get('/audit-logs/statistics', {
    params: dateRange,
  });
  return response.data;
};

// Export to CSV
const exportToCsv = async () => {
  const response = await auditClient.get('/audit-logs/export', {
    params: { format: 'csv' },
    responseType: 'blob',
  });
  
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'audit_logs.csv');
  document.body.appendChild(link);
  link.click();
};
```

### PHP/Laravel

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get('https://api.example.com/api/v1/audit-logs', [
        'event' => 'updated',
        'from_date' => '2024-01-01',
        'per_page' => 50,
    ]);

$auditLogs = $response->json('data');
```

## Module Structure

```
modules/Audit/
├── Http/
│   ├── Controllers/
│   │   └── AuditLogController.php      # Main API controller
│   ├── Requests/
│   │   └── IndexAuditLogRequest.php    # Validation for filtering
│   └── Resources/
│       └── AuditLogResource.php        # API response transformer
├── Models/
│   └── AuditLog.php                    # Audit log model
├── Policies/
│   └── AuditLogPolicy.php              # Authorization policy
├── Providers/
│   └── AuditServiceProvider.php        # Module service provider
└── routes/
    └── api.php                          # API route definitions
```

## Testing

```bash
# Run module tests
php artisan test --filter=Audit

# Test specific endpoint
php artisan test --filter=AuditLogControllerTest::testIndex

# Generate API documentation
php artisan l5-swagger:generate
```

## Best Practices

1. **Filter at the Source**: Apply filters as query parameters rather than client-side filtering
2. **Use Pagination**: Always use pagination for list endpoints to avoid performance issues
3. **Limit Exports**: Apply date range filters when exporting to limit result size
4. **Cache Statistics**: Cache statistics data to reduce database load
5. **Monitor Performance**: Track slow queries and optimize indexes as needed

## Support & Troubleshooting

### Common Issues

**Issue: "This action is unauthorized"**
- Ensure user has `audit.view` or `audit.export` permission
- Verify JWT token is valid and not expired

**Issue: "Maximum items per page is 100"**
- Reduce `per_page` parameter to 100 or less

**Issue: Export taking too long**
- Apply stricter date range filters
- Consider exporting in smaller batches

**Issue: Search not finding records**
- JSON search uses MySQL's `JSON_SEARCH` - ensure proper format
- Try exact matches first, then partial matches

## Changelog

### Version 1.0.0 (2024-01-15)
- Initial release
- Complete REST API implementation
- CSV/JSON export functionality
- Statistics and analytics endpoints
- Comprehensive filtering and search
- Production-ready with optimizations
