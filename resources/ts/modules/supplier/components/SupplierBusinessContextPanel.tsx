import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type { SupplierBusinessContextSummary } from '../types/supplier.types';

export function SupplierBusinessContextPanel({ summary }: { summary: SupplierBusinessContextSummary }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Open source documents', value: summary.openSourceDocuments },
                { label: 'Last source activity', value: summary.lastActivityDate },
                { label: 'Total source value', value: summary.totalActivityValue },
                { label: 'Payable balance', value: summary.payableBalance },
            ]}
            status="Mock backend preview"
            subtitle="Source usage, payable balances, and aging are backend-owned values."
            title="Supplier Context"
        />
    );
}
