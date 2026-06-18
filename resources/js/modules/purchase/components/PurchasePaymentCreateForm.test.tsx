import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ApiCollection } from '@/shared/types/api';
import type { Invoice } from '@/modules/invoice/invoiceApi';
import { PurchasePaymentCreateForm } from './PurchasePaymentCreateForm';

const purchaseApiMocks = vi.hoisted(() => ({
    createPurchasePayment: vi.fn(),
    getPurchaseOrder: vi.fn(),
    getPurchasePaymentContext: vi.fn(),
    listOutstandingSupplierInvoices: vi.fn(),
}));

vi.mock('../purchaseApi', () => purchaseApiMocks);
vi.mock('@/modules/invoice/invoiceApi', () => ({
    getInvoice: vi.fn(),
}));
vi.mock('./PurchaseLookups', () => ({
    SupplierLookupSelect: ({ value, onChange }: { value: { name?: string } | null; onChange: (value: { id: number; name: string }) => void }) => (
        <button type="button" onClick={() => onChange({ id: 11, name: 'Supplier A' })}>
            {value?.name ?? 'Select Supplier'}
        </button>
    ),
    CurrencyLookupSelect: ({ value }: { value: { code?: string; name?: string } | null }) => (
        <div>{value?.code ?? value?.name ?? 'Currency'}</div>
    ),
}));

describe('PurchasePaymentCreateForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        purchaseApiMocks.getPurchasePaymentContext.mockResolvedValue({
            payment_methods: [{ id: 1, name: 'Cash', method_type: 'cash' }],
            payment_accounts: [{ id: 2, name: 'Cash Account' }],
        });
        purchaseApiMocks.listOutstandingSupplierInvoices.mockResolvedValue(collection([invoice()]));
    });

    it('auto-loads paginated outstanding invoices after selecting a supplier', async () => {
        render(
            <MemoryRouter initialEntries={['/purchase/payments/create']}>
                <PurchasePaymentCreateForm />
            </MemoryRouter>,
        );

        expect(screen.queryByRole('button', { name: /load outstanding invoices/i })).not.toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Select Supplier' }));
        await userEvent.click(screen.getByRole('tab', { name: /Invoice Allocations/ }));

        await waitFor(() => expect(purchaseApiMocks.listOutstandingSupplierInvoices).toHaveBeenCalledWith(
            expect.objectContaining({ supplier_id: 11, page: 1, per_page: 10 }),
            expect.any(AbortSignal),
        ));
        const invoiceLinks = await screen.findAllByRole('link', { name: 'SI-42' });
        expect(invoiceLinks[0]).toHaveAttribute('href', '/invoices/42?from=purchase');
        expect(screen.getByText('1-1 of 2')).toBeInTheDocument();
    });
});

function invoice(): Invoice {
    return {
        id: 42,
        invoice_number: 'SI-42',
        invoice_date: '2026-06-18',
        invoice_type: 'purchase',
        direction: 'inbound',
        status: 'posted',
        party: { id: 11, name: 'Supplier A' },
        currency: { id: 5, code: 'USD', name: 'USD' },
        subtotal: '100.000000',
        grand_total: '100.000000',
        paid_amount: '0.000000',
        balance_due: '100.000000',
    } as Invoice;
}

function collection<T>(data: T[]): ApiCollection<T> {
    return {
        data,
        links: {},
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 2, path: '/', per_page: 10, to: data.length || null, total: 2 },
    };
}
