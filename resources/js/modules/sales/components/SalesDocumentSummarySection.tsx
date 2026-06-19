import { SalesSummaryPanel } from './SalesSummaryPanel';
import type { SalesDocumentTotals } from './salesDocumentFormUtils';

export function SalesDocumentSummarySection({ totals }: { totals: SalesDocumentTotals }) {
    return (
        <div className="xl:sticky xl:top-20 xl:self-start">
            <SalesSummaryPanel totals={{
                subtotal: totals.subtotal,
                line_discount_total: totals.discount_total,
                line_tax_total: totals.tax_total,
                line_charge_total: totals.charge_total,
                header_increase_total: totals.header_increase_total,
                header_decrease_total: totals.header_decrease_total,
                grand_total: totals.grand_total,
            }} />
        </div>
    );
}
