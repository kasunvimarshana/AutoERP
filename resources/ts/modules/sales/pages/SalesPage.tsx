import { useLocation } from 'react-router-dom';
import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

const sections = [
    { description: 'Customer commitments, pricing preview, and approval workflow.', label: 'Sales Orders', path: '/sales/orders', status: 'Ready' },
    { description: 'Deliveries/GDN and backend stock issue preview.', label: 'Deliveries / GDN', path: '/sales/deliveries', status: 'Mocked' },
    { description: 'Customer invoice drafts with backend tax, discounts, and totals.', label: 'Customer Invoices', path: '/sales/invoices', status: 'Mocked' },
    { description: 'Customer receipts and backend payment allocation preview.', label: 'Customer Payments', path: '/sales/payments', status: 'Mocked' },
    { description: 'Customer advances and later allocation.', label: 'Advances', path: '/sales/advances', status: 'Planned' },
    { description: 'Sales returns, refunds, and reversal effects.', label: 'Returns and Refunds', path: '/sales/returns', status: 'Planned' },
];

const titles: Record<string, string> = {
    '/sales/advances': 'Customer Advances',
    '/sales/deliveries': 'Deliveries / GDN',
    '/sales/invoices': 'Customer Invoices',
    '/sales/orders': 'Sales Orders',
    '/sales/payments': 'Customer Payments',
    '/sales/refunds': 'Customer Refunds',
    '/sales/returns': 'Sales Returns',
};

export function SalesPage() {
    const { pathname } = useLocation();

    return (
        <ModulePlaceholderPage
            actions={[
                { label: 'New Sales Order', path: '/sales/orders/new', variant: 'primary' },
                { label: 'Price Preview', path: '/pricing/price-lists', variant: 'secondary' },
            ]}
            description="Sales orders, deliveries, customer invoices, payments, advances, returns, and refunds. Backend owns pricing, tax, discounts, stock issue, AR postings, allocations, and reversals."
            metrics={[
                { helper: 'Mock operational count', label: 'Open customer documents', value: 42 },
                { helper: 'Backend workflow status later', label: 'Pending dispatch', value: 11 },
                { helper: 'Backend preview placeholder', label: 'Invoice previews', value: 9 },
            ]}
            sections={pathname === '/sales' ? sections : undefined}
            title={titles[pathname] ?? 'Sales Dashboard'}
        />
    );
}
