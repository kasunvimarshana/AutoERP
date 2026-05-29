import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type { SupplierPurchaseUsageSummary } from '../types/supplier.types';

export function SupplierPurchaseUsagePanel({ summary }: { summary: SupplierPurchaseUsageSummary }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Open purchase orders', value: summary.openPurchaseOrders },
                { label: 'Last purchase date', value: summary.lastPurchaseDate },
                { label: 'Total purchases', value: summary.totalPurchases },
                { label: 'Payable balance', value: summary.payableBalance },
            ]}
            status="Mock backend preview"
            subtitle="Purchase usage, payable balances, and aging are backend-owned values."
            title="Purchase Usage"
        />
    );
}
