# Reporting Module

## Overview
The Reporting module provides comprehensive analytics and KPIs for the ERP platform. It aggregates data from multiple modules (Billing, CRM, Inventory, POS) to generate insightful reports and dashboards.

## Features

### Reports
- **Sales Report**: Comprehensive sales analysis including POS and invoice data
- **Inventory Report**: Stock levels, warehouse distribution, and inventory value
- **Customer Report**: Customer engagement metrics and lifetime value analysis
- **Revenue KPIs**: Period-based revenue analysis with growth tracking

### Analytics
- **Top Products**: Best-selling products by revenue
- **Top Customers**: Highest-value customers by total revenue
- **Sales Trends**: Daily breakdown of sales over time
- **Dashboard Summary**: Consolidated view of key metrics

## API Endpoints

### Sales Reports
```
GET /api/reporting/sales
Query Params:
  - start_date (required): YYYY-MM-DD
  - end_date (required): YYYY-MM-DD

Response: SalesReportResource
```

```
GET /api/reporting/sales/trends
Query Params:
  - start_date (required): YYYY-MM-DD
  - end_date (required): YYYY-MM-DD

Response: Daily sales breakdown with trends
```

### Inventory Report
```
GET /api/reporting/inventory

Response: InventoryReportResource
```

### Customer Report
```
GET /api/reporting/customers

Response: CustomerReportResource
```

### Revenue KPIs
```
GET /api/reporting/revenue/kpis
Query Params:
  - period (required): daily|weekly|monthly|yearly

Response: Revenue KPIs with growth metrics
```

### Top Performers
```
GET /api/reporting/top-products
Query Params:
  - limit (optional, default: 10): Number of products to return (1-100)

Response: Top products by revenue
```

```
GET /api/reporting/top-customers
Query Params:
  - limit (optional, default: 10): Number of customers to return (1-100)

Response: Top customers by revenue
```

### Dashboard
```
GET /api/reporting/dashboard

Response: Consolidated dashboard summary with all key metrics
```

## Architecture

### Services
- **ReportingService**: Core service containing all report generation logic
  - Aggregates data from multiple modules
  - Calculates KPIs and metrics
  - Performs date-based filtering and grouping

### Controllers
- **ReportingController**: REST API controller exposing reports as endpoints
  - Input validation
  - Response formatting
  - Error handling

### Resources
- **SalesReportResource**: Formats sales report data
- **InventoryReportResource**: Formats inventory report data
- **CustomerReportResource**: Formats customer report data

### DTOs
- **ReportFilterDTO**: Standardized filtering parameters for reports
  - Date ranges
  - Limits
  - Sorting options
  - Custom filters

## Data Sources

The Reporting module aggregates data from:
- **Billing Module**: Invoices, Payments
- **CRM Module**: Customers, Customer relationships
- **Inventory Module**: Products, SKUs, Stock movements
- **POS Module**: Transactions, Transaction items

## Usage Examples

### Get Monthly Sales Report
```bash
curl -X GET "http://api.example.com/api/reporting/sales?start_date=2024-01-01&end_date=2024-01-31"
```

### Get Revenue KPIs
```bash
curl -X GET "http://api.example.com/api/reporting/revenue/kpis?period=monthly"
```

### Get Top 5 Products
```bash
curl -X GET "http://api.example.com/api/reporting/top-products?limit=5"
```

### Get Dashboard Summary
```bash
curl -X GET "http://api.example.com/api/reporting/dashboard"
```

## Performance Considerations

- Reports use database aggregations for efficiency
- Consider caching frequently accessed reports
- Large date ranges may impact performance
- Use pagination limits for top performer queries

## Future Enhancements

Potential additions:
- Export reports to PDF/Excel
- Scheduled report generation
- Email report delivery
- Custom report builder
- Advanced filtering options
- Real-time dashboard updates
- Comparative period analysis
- Forecasting and predictions
