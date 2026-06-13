import { PurchaseOrderSummaryPanel } from '@/modules/purchase/components/PurchaseOrderSummaryPanel';
import type { SalesDocumentTotals } from './salesDocumentFormUtils';

export function SalesDocumentSummarySection({ totals }: { totals: SalesDocumentTotals }) {
    return (
        <div className="xl:sticky xl:top-20 xl:self-start">
            <PurchaseOrderSummaryPanel totals={totals} />
        </div>
    );
}
