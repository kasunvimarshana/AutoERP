# Reporting Module

## Overview

The Reporting module provides comprehensive reporting, analytics, and dashboard capabilities for the enterprise ERP/CRM platform. It enables users to create custom reports, visualize data through dashboards, export data in various formats, and schedule automated report generation.

## Features

### 1. Report Builder
- **Dynamic Query Building**: Create custom reports with flexible query configurations
- **Multiple Report Types**: Sales, Financial, Inventory, CRM, Purchase, and Custom reports
- **Multiple Formats**: Table, Chart, Summary, and Pivot table views
- **Advanced Filtering**: Support for complex filters with multiple operators
- **Grouping & Sorting**: Group data by multiple fields and apply custom sorting
- **Aggregations**: SUM, AVG, COUNT, MIN, MAX with BCMath precision

### 2. Report Execution
- **Query Execution**: Execute reports with runtime filters
- **Performance Tracking**: Track execution time and result counts
- **Execution History**: Log all report executions with parameters
- **Tenant Isolation**: Automatic tenant and organization scoping

### 3. Data Export
- **CSV Export**: Native PHP CSV generation
- **JSON Export**: Structured JSON export with metadata
- **Stream Downloads**: Memory-efficient streaming for large datasets
- **File Storage**: Automatic export file management with cleanup

### 4. Dashboards
- **Widget Composition**: Add multiple widgets to dashboards
- **Widget Types**: KPI cards, Charts, Tables, Summaries, Metrics
- **Flexible Layouts**: Grid-based layout with customizable positioning
- **Default Dashboard**: Set user-specific default dashboards
- **Dashboard Sharing**: Share dashboards across organization
- **Dashboard Cloning**: Duplicate dashboards with all widgets

### 5. Analytics
- **Pre-built Analytics**: Sales, Inventory, CRM, and Financial metrics
- **Top Products Analysis**: Best-selling products tracking
- **Customer Analytics**: Customer purchase patterns and values
- **Trend Analysis**: Time-series data with configurable intervals
- **BCMath Calculations**: Precision-safe financial calculations

### 6. Scheduled Reports
- **Frequency Options**: Daily, Weekly, Monthly, Quarterly, Yearly
- **Cron Scheduling**: Flexible cron-based scheduling
- **Auto Export**: Automatic export in specified formats
- **Email Distribution**: Send reports to multiple recipients
- **Execution Tracking**: Log scheduled execution results

## Architecture

### Models
- **Report**: Report definitions with query configurations
- **SavedReport**: User-saved reports with custom filters
- **Dashboard**: Dashboard containers
- **DashboardWidget**: Individual dashboard widgets
- **ReportSchedule**: Scheduled report configurations
- **ReportExecution**: Report execution logs

### Services
- **ReportBuilderService**: Query building and execution
- **ReportExportService**: Data export functionality
- **DashboardService**: Dashboard and widget management
- **AnalyticsService**: Pre-built analytics and metrics
- **ScheduledReportService**: Schedule management and execution

### Repositories
- **ReportRepository**: Report data access
- **SavedReportRepository**: Saved report operations
- **DashboardRepository**: Dashboard CRUD
- **WidgetRepository**: Widget management
- **ScheduleRepository**: Schedule operations
- **ExecutionRepository**: Execution history

## API Endpoints

### Reports
```
GET    /api/v1/reports              - List reports
POST   /api/v1/reports              - Create report
GET    /api/v1/reports/templates    - Get report templates
GET    /api/v1/reports/{id}         - Get report details
PUT    /api/v1/reports/{id}         - Update report
DELETE /api/v1/reports/{id}         - Delete report

POST   /api/v1/reports/{id}/execute - Execute report
POST   /api/v1/reports/{id}/export  - Export report
POST   /api/v1/reports/{id}/publish - Publish report
POST   /api/v1/reports/{id}/archive - Archive report
```

### Dashboards
```
GET    /api/v1/dashboards              - List dashboards
POST   /api/v1/dashboards              - Create dashboard
GET    /api/v1/dashboards/default      - Get default dashboard
GET    /api/v1/dashboards/{id}         - Get dashboard
PUT    /api/v1/dashboards/{id}         - Update dashboard
DELETE /api/v1/dashboards/{id}         - Delete dashboard

GET    /api/v1/dashboards/{id}/render      - Render dashboard with data
POST   /api/v1/dashboards/{id}/set-default - Set as default
POST   /api/v1/dashboards/{id}/clone       - Clone dashboard
```

### Widgets
```
POST   /api/v1/widgets           - Create widget
GET    /api/v1/widgets/{id}      - Get widget
PUT    /api/v1/widgets/{id}      - Update widget
DELETE /api/v1/widgets/{id}      - Delete widget
PATCH  /api/v1/widgets/{id}/position - Update position

POST   /api/v1/dashboards/{id}/widgets/reorder - Reorder widgets
```

### Analytics
```
GET    /api/v1/analytics/sales        - Sales metrics
GET    /api/v1/analytics/inventory    - Inventory metrics
GET    /api/v1/analytics/crm          - CRM metrics
GET    /api/v1/analytics/financial    - Financial metrics
GET    /api/v1/analytics/top-products - Top selling products
GET    /api/v1/analytics/customers    - Customer analytics
GET    /api/v1/analytics/trend        - Trend analysis
```

## Usage Examples

### Creating a Custom Report

```php
POST /api/v1/reports
{
    "name": "Monthly Sales Report",
    "description": "Sales performance for the month",
    "type": "sales",
    "format": "table",
    "query_config": {
        "table": "sales_invoices",
        "tenant_scoped": true,
        "organization_scoped": true,
        "joins": [
            {
                "type": "left",
                "table": "customers",
                "first": "sales_invoices.customer_id",
                "operator": "=",
                "second": "customers.id"
            }
        ]
    },
    "fields": [
        "sales_invoices.invoice_code",
        "customers.name as customer_name",
        "sales_invoices.total_amount",
        "sales_invoices.invoice_date"
    ],
    "filters": [
        {
            "field": "invoice_date",
            "operator": ">=",
            "value": "2024-01-01"
        }
    ],
    "sorting": [
        {
            "field": "invoice_date",
            "direction": "desc"
        }
    ]
}
```

### Executing a Report

```php
POST /api/v1/reports/{id}/execute
{
    "filters": [
        {
            "field": "total_amount",
            "operator": ">",
            "value": 1000
        }
    ]
}
```

### Creating a Dashboard

```php
POST /api/v1/dashboards
{
    "name": "Sales Dashboard",
    "description": "Overview of sales performance",
    "is_default": true,
    "layout": {
        "columns": 12,
        "rows": "auto"
    }
}
```

### Adding a Widget

```php
POST /api/v1/widgets
{
    "dashboard_id": 1,
    "report_id": 5,
    "type": "chart",
    "chart_type": "line",
    "title": "Monthly Revenue",
    "width": 6,
    "height": 4,
    "position_x": 0,
    "position_y": 0,
    "refresh_interval": 300,
    "data_source": {
        "filters": []
    }
}
```

### Exporting a Report

```php
POST /api/v1/reports/{id}/export
{
    "format": "csv",
    "stream": true,
    "filters": []
}
```

## Configuration

Configuration is located in `modules/Reporting/Config/reporting.php`:

```php
return [
    'enabled' => true,
    
    'exports' => [
        'storage_disk' => 'local',
        'storage_path' => 'exports',
        'cleanup_days' => 7,
        'max_file_size' => 10485760, // 10MB
    ],
    
    'execution' => [
        'timeout' => 300,
        'max_results' => 10000,
        'chunk_size' => 1000,
    ],
    
    'scheduling' => [
        'enabled' => true,
        'queue' => 'reports',
        'max_retries' => 3,
    ],
    
    'dashboards' => [
        'max_widgets' => 20,
        'default_refresh_interval' => 300,
        'grid_columns' => 12,
    ],
];
```

## Scheduled Reports

Scheduled reports run automatically based on configured frequency:

```php
$schedule = new ReportSchedule([
    'report_id' => 1,
    'name' => 'Weekly Sales Report',
    'frequency' => 'weekly',
    'recipients' => [
        ['user_id' => 1],
        ['email' => 'manager@example.com']
    ],
    'export_formats' => ['csv', 'json'],
    'is_active' => true,
]);
```

Schedules are executed every 5 minutes by the scheduler:
- Checks for due schedules
- Executes reports
- Exports in specified formats
- Notifies recipients

## Events

The module dispatches the following events:

- **ReportGenerated**: When a report is executed
- **ReportExported**: When a report is exported
- **ReportPublished**: When a report is published
- **DashboardCreated**: When a dashboard is created
- **ScheduledReportExecuted**: When a scheduled report runs

## Permissions

Required permissions:
- `reports.create` - Create reports
- `reports.force_delete` - Force delete reports
- `dashboards.create` - Create dashboards
- `dashboards.force_delete` - Force delete dashboards
- `widgets.create` - Create widgets
- `widgets.force_delete` - Force delete widgets

## Database Tables

- **reports** - Report definitions
- **saved_reports** - User-saved reports
- **dashboards** - Dashboard containers
- **dashboard_widgets** - Widget configurations
- **report_schedules** - Scheduled report configurations
- **report_executions** - Execution history and logs

## Security Features

- **Tenant Isolation**: All reports scoped to tenant
- **Organization Scoping**: Optional organization-level filtering
- **User Permissions**: Policy-based authorization
- **Data Privacy**: Users can only access their own or shared reports
- **Query Validation**: Prevent SQL injection through query builder
- **Export Cleanup**: Automatic cleanup of old export files

## Performance Considerations

- **Query Optimization**: Indexed database columns for fast lookups
- **Chunked Processing**: Large datasets processed in chunks
- **Stream Exports**: Memory-efficient streaming for large exports
- **Result Limits**: Configurable maximum result counts
- **Caching**: Analytics results can be cached
- **BCMath**: Precision calculations without floating-point errors

## Integration

The Reporting module integrates with:
- **Sales Module**: Sales reports and analytics
- **Inventory Module**: Stock and valuation reports
- **CRM Module**: Lead and opportunity analytics
- **Accounting Module**: Financial reports
- **Notification Module**: Report distribution via notifications
- **Audit Module**: Audit logging for report operations

## Development Notes

- Uses native Laravel features exclusively
- No third-party reporting libraries
- BCMath for all financial calculations
- Follows Clean Architecture principles
- Repository pattern for data access
- Service layer for business logic
- Policy-based authorization
- Comprehensive event system

## Testing

Test coverage includes:
- Unit tests for services and repositories
- Feature tests for API endpoints
- Policy tests for authorization
- Integration tests for report execution
- Export functionality tests

## Future Enhancements

Potential future additions:
- PDF export (currently placeholder)
- Advanced chart types
- Report versioning
- Custom SQL queries for advanced users
- Report sharing with external users
- API access for external tools
- Advanced caching strategies
- Real-time dashboard updates via WebSockets

## Support

For issues or questions related to the Reporting module, please refer to the main project documentation or contact the development team.
