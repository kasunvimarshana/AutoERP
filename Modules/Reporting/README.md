# Reporting Module

## Overview

The **Reporting** module provides enterprise-grade reporting capabilities across all platform modules. All reports are tenant-scoped, filterable, exportable, and must never break transactional integrity.

---

## Supported Reports

- Aggregated financial statements (P&L, Balance Sheet, Trial Balance)
- Inventory valuation reports (by costing method)
- Aging reports (accounts receivable / payable)
- Tax summaries
- Inventory turnover analysis
- Sales performance reports
- Procurement spend analysis
- Custom report builder

## Export Formats

- CSV
- PDF

---

## Architecture Layer

```
Modules/Reporting/
 ├── Application/       # Generate report, schedule report, export report use cases
 ├── Domain/            # ReportDefinition entity, ReportFilter value objects
 ├── Infrastructure/    # ReportingRepository, ReportingServiceProvider, PDF/CSV generators
 ├── Interfaces/        # ReportController, ReportExportController
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| All reports tenant-scoped | ✅ Enforced |
| Reports never break transactional integrity | ✅ Required |
| Reports filterable, exportable, and auditable | ✅ Required |
| No cross-module coupling (reads via published contracts/read models) | ✅ Enforced |

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
