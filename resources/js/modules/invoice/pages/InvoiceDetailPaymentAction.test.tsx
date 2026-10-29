import { render, screen } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import InvoiceDetailPage from './InvoiceDetailPage';

const invoiceApi = vi.hoisted(() => ({ getInvoice: vi.fn() }));
const auth = vi.hoisted(() => ({ permissions: ['purchase.payments.execute'] }));

vi.mock('../invoiceApi', () => invoiceApi);
vi.mock('@/modules/auth/useTenantRouteAccess', () => ({ useTenantRouteAccess: () => true }));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissions: auth.permissions, permissionsLoaded: true }),
}));

const supplierInvoice = {
    id: 32,
    row_version: 1,
    invoice_number: 'PURCHASE-2026-000002',
    invoice_type: 'purchase',
    direction: 'inbound',
    status: 'posted',
    grand_total: '1200.000000',
    paid_total: '0.000000',
    credit_total: '0.000000',
    balance_due: '1200.000000',
};

describe('supplier invoice payment action', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        auth.permissions = ['purchase.payments.execute'];
        invoiceApi.getInvoice.mockResolvedValue(supplierInvoice);
    });

    it.each(['/invoices/32', '/purchase/invoices/32'])('shows Create Payment at %s', async (path) => {
        render(<TestRouter initialEntries={[path]}>
            <Routes>
                <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                <Route path="/purchase/invoices/:id" element={<InvoiceDetailPage />} />
            </Routes>
        </TestRouter>);

        expect(await screen.findByRole('link', { name: 'Create Payment' })).toHaveAttribute('href', '/purchase/payments/create?invoice_id=32');
    });

    it('hides the action after full settlement', async () => {
        invoiceApi.getInvoice.mockResolvedValue({ ...supplierInvoice, status: 'paid', paid_total: '1200.000000', balance_due: '0.000000' });
        render(<TestRouter initialEntries={['/invoices/32']}>
            <Routes><Route path="/invoices/:id" element={<InvoiceDetailPage />} /></Routes>
        </TestRouter>);

        expect(await screen.findByText('PURCHASE-2026-000002')).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Create Payment' })).not.toBeInTheDocument();
    });

    it('hides the action without supplier payment permission', async () => {
        auth.permissions = [];
        render(<TestRouter initialEntries={['/invoices/32']}>
            <Routes><Route path="/invoices/:id" element={<InvoiceDetailPage />} /></Routes>
        </TestRouter>);

        expect(await screen.findByText('PURCHASE-2026-000002')).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Create Payment' })).not.toBeInTheDocument();
    });

    it('opens the existing Selling payment entry for an outstanding sales invoice', async () => {
        auth.permissions = ['payments.create', 'payment-methods.view', 'selling.sales.create'];
        invoiceApi.getInvoice.mockResolvedValue({ ...supplierInvoice, invoice_type: 'sales', direction: 'outbound' });
        render(<TestRouter initialEntries={['/invoices/32']}>
            <Routes><Route path="/invoices/:id" element={<InvoiceDetailPage />} /></Routes>
        </TestRouter>);

        expect(await screen.findByRole('link', { name: 'Receive payment' }))
            .toHaveAttribute('href', '/payments/create?invoice_id=32&view=selling');
        expect(screen.queryByRole('link', { name: 'Create Payment' })).not.toBeInTheDocument();
    });

    it('hides the sales payment action without Selling payment permission', async () => {
        auth.permissions = ['payments.create'];
        invoiceApi.getInvoice.mockResolvedValue({ ...supplierInvoice, invoice_type: 'sales', direction: 'outbound' });
        render(<TestRouter initialEntries={['/invoices/32']}>
            <Routes><Route path="/invoices/:id" element={<InvoiceDetailPage />} /></Routes>
        </TestRouter>);

        expect(await screen.findByText('PURCHASE-2026-000002')).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Receive payment' })).not.toBeInTheDocument();
    });

    it('passes the exact service invoice to its linked job payment screen', async () => {
        auth.permissions = ['vehicle_service.payments.create'];
        invoiceApi.getInvoice.mockResolvedValue({
            ...supplierInvoice,
            invoice_type: 'service',
            direction: 'outbound',
            sources: [{ source_type: 'vehicle_service_job', source_id: 9 }],
        });
        render(<TestRouter initialEntries={['/invoices/32']}>
            <Routes><Route path="/invoices/:id" element={<InvoiceDetailPage />} /></Routes>
        </TestRouter>);

        expect(await screen.findByRole('link', { name: 'Pay this invoice' }))
            .toHaveAttribute('href', '/vehicle-service/jobs/9/payment?invoice_id=32');
    });
});
