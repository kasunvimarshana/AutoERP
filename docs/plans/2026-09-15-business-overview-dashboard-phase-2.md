# Business Overview Dashboard Phase 2

Date: 2026-09-15
Status: Approved for implementation

## Goal

Extend the Business Overview dashboard with decision-ready employee performance and authoritative profitability without duplicating business calculations.

## Implemented scope

- Add a five-row employee ranking sourced from the canonical Employee Commission Report query.
- Rank employees by effective non-cancelled labour value, then commission.
- Show completed and assigned jobs, earned and pending commission, and labour value.
- Preserve the dashboard date range when opening the Employee Commission Report.
- Add selected-period profitability sourced from the ledger-backed Summary Report service.
- Show revenue, cost of sales, gross profit, other expenses, and net profit.
- Keep the Finance Summary Report as the drill-down and source of truth.

## Data integrity

- Cancelled commission and labour values do not contribute to effective performance.
- Historical commission remains available in the canonical report data.
- Profitability uses posted Finance balances and never approximates profit from invoices or purchases.
- Tenant and current organization-unit scope remain enforced by the existing request context.

## Not included

- Cross-organization-unit consolidation requires a separate authorized aggregation contract because current Finance and operational services intentionally scope one organization unit.
- Historical as-of aging requires an authoritative historical invoice-balance service. Current invoice balance fields represent the present position and must not be used to invent past balances.
