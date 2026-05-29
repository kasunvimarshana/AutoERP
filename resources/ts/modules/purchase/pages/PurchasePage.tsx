import { useLocation } from 'react-router-dom';
import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

const sections = [
    { description: 'Supplier commitments, order lines, approvals, and source references.', label: 'Purchase Orders', path: '/purchase/orders', status: 'Ready' },
    { description: 'Goods receipt notes and inventory receive workflow.', label: 'GRNs', path: '/purchase/grns', status: 'Mocked' },
    { description: 'Supplier document capture with backend invoice preview.', label: 'Supplier Invoices', path: '/purchase/invoices', status: 'Mocked' },
    { description: 'Supplier payment workflow and backend allocation preview.', label: 'Supplier Payments', path: '/purchase/payments', status: 'Mocked' },
    { description: 'Advance payments and later allocation to supplier invoices.', label: 'Advances', path: '/purchase/advances', status: 'Planned' },
    { description: 'Purchase returns and supplier refund workflow.', label: 'Returns and Refunds', path: '/purchase/returns', status: 'Planned' },
];

const titles: Record<string, string> = {
    '/purchase/advances': 'Purchase Advances',
    '/purchase/grns': 'Goods Receipt Notes',
    '/purchase/invoices': 'Supplier Invoices',
    '/purchase/orders': 'Purchase Orders',
    '/purchase/payments': 'Supplier Payments',
    '/purchase/refunds': 'Supplier Refunds',
    '/purchase/returns': 'Purchase Returns',
};

export function PurchasePage() {
    const { pathname } = useLocation();

    return (
        <ModulePlaceholderPage
            actions={[
                { label: 'New Purchase Order', path: '/purchase/orders/new', variant: 'primary' },
                { label: 'Invoice Preview', path: '/purchase/invoices', variant: 'secondary' },
            ]}
            description="Purchase orders, GRNs, supplier invoices, payments, advances, returns, and refunds. Backend owns totals, tax, stock receipt, AP postings, allocations, and reversals."
            metrics={[
                { helper: 'Mock operational count', label: 'Open supplier documents', value: 31 },
                { helper: 'Backend approval status later', label: 'Awaiting approval', value: 7 },
                { helper: 'Backend preview placeholder', label: 'Posting previews', value: 5 },
            ]}
            sections={pathname === '/purchase' ? sections : undefined}
            title={titles[pathname] ?? 'Purchase Dashboard'}
        />
    );
}
