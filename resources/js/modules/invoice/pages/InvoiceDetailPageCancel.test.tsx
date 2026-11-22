import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import InvoiceDetailPage from './InvoiceDetailPage';

const invoiceApi = vi.hoisted(() => ({
    getInvoice: vi.fn(),
    approveInvoice: vi.fn(),
    postInvoice: vi.fn(),
    cancelInvoice: vi.fn(),
    getInvoiceSignedPrintLink: vi.fn(),
}));
const auth = vi.hoisted(() => ({ permissions: ['invoices.cancel', 'invoices.manage'] }));

vi.mock('../invoiceApi', () => invoiceApi);
vi.mock('../invoiceLayoutApi', () => ({ listInvoiceLayouts: vi.fn().mockResolvedValue([]) }));
vi.mock('@/modules/auth/useTenantRouteAccess', () => ({ useTenantRouteAccess: () => true }));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissions: auth.permissions, permissionsLoaded: true }),
}));

const draftInvoice = {
    id: 42,
    row_version: 1,
    invoice_number: 'INV-2026-000042',
    invoice_type: 'customer',
    direction: 'outbound',
    status: 'draft',
    grand_total: '500.000000',
    paid_total: '0.000000',
    credit_total: '0.000000',
    balance_due: '500.000000',
};

describe('InvoiceDetailPage cancellation modal dialog', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        auth.permissions = ['invoices.cancel', 'invoices.manage'];
        invoiceApi.getInvoice.mockResolvedValue(draftInvoice);
    });

    it('opens modal, cancels invoice with entered reason, and updates state', async () => {
        invoiceApi.cancelInvoice.mockResolvedValue({
            ...draftInvoice,
            status: 'cancelled',
            row_version: 2,
        });

        render(
            <TestRouter initialEntries={['/invoices/42']}>
                <Routes>
                    <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                </Routes>
            </TestRouter>,
        );

        // Wait for page to load
        expect(await screen.findByText('INV-2026-000042')).toBeInTheDocument();

        // Cancel button in header
        const openCancelBtn = screen.getByRole('button', { name: 'Cancel' });
        fireEvent.click(openCancelBtn);

        // Modal should open
        expect(await screen.findByRole('dialog')).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Cancel Invoice' })).toBeInTheDocument();
        expect(screen.getByText(/Please provide a reason for cancelling invoice/)).toBeInTheDocument();

        // Type reason
        const input = screen.getByRole('textbox', { name: 'Cancellation Reason' });
        fireEvent.change(input, { target: { value: 'Customer requested cancellation' } });

        // Confirm cancellation
        const confirmBtn = screen.getByRole('button', { name: 'Confirm Cancellation' });
        fireEvent.click(confirmBtn);

        await waitFor(() => {
            expect(invoiceApi.cancelInvoice).toHaveBeenCalledWith(42, 1, 'Customer requested cancellation');
        });

        // Modal closes on success
        await waitFor(() => {
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });
    });

    it('closes modal when Keep Invoice is clicked without calling cancelInvoice', async () => {
        render(
            <TestRouter initialEntries={['/invoices/42']}>
                <Routes>
                    <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                </Routes>
            </TestRouter>,
        );

        expect(await screen.findByText('INV-2026-000042')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(await screen.findByRole('dialog')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Keep Invoice' }));

        await waitFor(() => {
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });
        expect(invoiceApi.cancelInvoice).not.toHaveBeenCalled();
    });

    it('displays error inside modal if cancelInvoice fails', async () => {
        invoiceApi.cancelInvoice.mockRejectedValue(new Error('Cannot cancel invoice with posted lines'));

        render(
            <TestRouter initialEntries={['/invoices/42']}>
                <Routes>
                    <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                </Routes>
            </TestRouter>,
        );

        expect(await screen.findByText('INV-2026-000042')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(await screen.findByRole('dialog')).toBeInTheDocument();

        const confirmBtn = screen.getByRole('button', { name: 'Confirm Cancellation' });
        fireEvent.click(confirmBtn);

        await waitFor(() => {
            expect(screen.getByText('Cannot cancel invoice with posted lines')).toBeInTheDocument();
        });

        // Modal stays open so user sees error
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });
});
