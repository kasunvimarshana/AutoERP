# Old-to-current report menu map review

Date: 2026-10-29

Read the attached old-system report titles and verified the current report catalog, navigation, and router. This is a documentation-only review; application code and data were not changed.

| Old sample | Current screen | Route |
| --- | --- | --- |
| Payment Method Wise Summary Report (R.pdf discussion) | Payment Method Wise Summary | `/reports/payment-method-wise-summary` |
| Stock Card (R2) | Stock Card, under Inventory | `/inventory/stock-card` |
| Stock balance Report (R3) | Stock Balance, existing report | `/reports/inventory.stock-balance` |
| Stock Valuation Report (R4) | Inventory Valuation Report | `/reports/inventory.valuation` |
| Item Wise Ageing (R5) | Item Wise Ageing Report | `/reports/inventory.aging` |
| Purchase Order Detail Report (R6) | Purchase Order Detail Report | `/reports/purchase/detailed` |
| Goods Recieve Note Summary Report (R7) | Goods Receipt Note Summary Report | `/reports/purchase/goods-receipt-summary` |
| Invoice Wise Debtors Balance as at [date] (R8) | Customer Outstanding | `/reports/customer-outstanding` |
| Incentive Summary Report (R10) | Employee Commission | `/reports/vehicle-service/employee-commissions` |

The separately developed Vehicle Service History page is at `/reports/vehicle-service/service-history`; it was not one of those numbered PDF report titles. R3 was already available and was not newly developed during this set of tasks. R1 is an Invoice Wise Sales Report and should not be confused with the payment-method summary.
