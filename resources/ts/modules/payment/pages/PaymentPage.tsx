import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function PaymentPage() {
    return (
        <ModulePlaceholderPage
            description="Cross-module payment console for methods, allocations, advances, refunds, write-offs, cash registers, and cheques. Backend owns settlement, balances, postings, idempotency, and reversals."
            sections={[
                { description: 'All payments across source modules for finance review.', label: 'Payments', path: '/payments', status: 'Ready' },
                { description: 'Backend allocation preview and settlement results.', label: 'Allocations', path: '/payments', status: 'Mocked' },
                { description: 'Refund and write-off workflow placeholders.', label: 'Refunds / Write-offs', path: '/payments', status: 'Mocked' },
            ]}
            title="Payments"
        />
    );
}
