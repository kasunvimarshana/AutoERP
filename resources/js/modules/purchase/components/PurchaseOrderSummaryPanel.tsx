import { PurchaseTotalsSummary } from './PurchaseTotalsSummary';

export interface PurchaseTotals {
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    header_increase_total: string;
    header_decrease_total: string;
    grand_total: string;
}

export function PurchaseOrderSummaryPanel({ totals }: { totals: PurchaseTotals }) {
    return <PurchaseTotalsSummary rows={[
        { label: 'Subtotal', value: totals.subtotal },
        { label: 'Line Discounts', value: totals.discount_total },
        { label: 'Taxes', value: totals.tax_total },
        { label: 'Freight / Charges', value: totals.charge_total },
        { label: 'Header Increases', value: totals.header_increase_total },
        { label: 'Header Discounts', value: totals.header_decrease_total },
        { label: 'Grand Total', value: totals.grand_total, strong: true },
    ]} />;
}
