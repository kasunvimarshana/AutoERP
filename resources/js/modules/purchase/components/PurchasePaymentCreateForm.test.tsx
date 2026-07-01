import { StrictMode } from 'react';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes, useNavigate } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ApiCollection } from '@/shared/types/api';
import type { Invoice } from '@/modules/invoice/invoiceApi';
import { TestRouter } from '@/test/TestRouter';
import { PurchasePaymentCreateForm } from './PurchasePaymentCreateForm';

const purchaseApiMocks = vi.hoisted(() => ({
    createPurchasePayment: vi.fn(),
    getPurchaseOrder: vi.fn(),
    getPurchasePaymentContext: vi.fn(),
    listOutstandingSupplierInvoices: vi.fn(),
    preparePurchasePayment: vi.fn(),
}));
const invoiceApiMocks = vi.hoisted(() => ({
    getInvoice: vi.fn(),
}));

vi.mock('../purchaseApi', () => purchaseApiMocks);
vi.mock('@/modules/invoice/invoiceApi', () => invoiceApiMocks);
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
        });
        purchaseApiMocks.listOutstandingSupplierInvoices.mockResolvedValue(collection([invoice()]));
        purchaseApiMocks.preparePurchasePayment.mockResolvedValue({
            tenant_id: 1,
            organization_unit_id: null,
            payment_date: '2026-06-18',
            amount: '100.000000',
            line_total: '100.000000',
            allocation_total: '100.000000',
            unapplied_amount: '0.000000',
            supplier_type: 'supplier',
            supplier_id: 11,
            currency_id: 5,
            exchange_rate: '1.000000',
            reference_number: null,
            lines: [{ amount: '100.000000', payment_method_id: 1 }],
            allocations: [{
                invoice_id: 42,
                invoice_number: 'SI-42',
                invoice_total: '100.000000',
                invoice_balance_before: '100.000000',
                allocated_amount: '100.000000',
                invoice_balance_after: '0.000000',
                allocation_date: '2026-06-18',
                allocation_method: 'specific_invoice',
            }],
        });
        invoiceApiMocks.getInvoice.mockResolvedValue(invoice());
    });

    it('auto-loads paginated outstanding invoices after selecting a supplier', async () => {
        render(
            <TestRouter initialEntries={['/purchase/payments/create']}>
                <PurchasePaymentCreateForm />
            </TestRouter>,
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

    it('previews a purchase payment without creating it', async () => {
        render(
            <TestRouter initialEntries={['/purchase/payments/prepare']}>
                <PurchasePaymentCreateForm mode="prepare" />
            </TestRouter>,
        );

        const user = userEvent.setup();
        await user.click(screen.getByRole('button', { name: 'Select Supplier' }));
        await user.click(screen.getByRole('tab', { name: /Invoice Allocations/ }));

        await screen.findAllByRole('link', { name: 'SI-42' });
        const allocationInput = screen.getAllByLabelText('Allocation')[0];
        fireEvent.change(allocationInput, { target: { value: '100.000000' } });

        await user.click(screen.getByRole('tab', { name: /Payment Methods/ }));
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '1');

        await user.click(screen.getByRole('button', { name: 'Preview Payment' }));

        await waitFor(() => expect(purchaseApiMocks.preparePurchasePayment).toHaveBeenCalledWith(expect.objectContaining({
            supplier_id: 11,
            amount: '100.000000',
        })));
        expect(purchaseApiMocks.createPurchasePayment).not.toHaveBeenCalled();
        expect(await screen.findByText('Payment Preview')).toBeInTheDocument();
        expect(screen.getByText('SI-42')).toBeInTheDocument();
    });

    it('loads an initial invoice source once under StrictMode', async () => {
        render(
            <StrictMode>
                <TestRouter initialEntries={['/purchase/payments/create?invoice_id=42']}>
                    <PurchasePaymentCreateForm />
                </TestRouter>
            </StrictMode>,
        );

        expect(await screen.findByText('Loaded supplier invoice SI-42.')).toBeInTheDocument();
        expect(invoiceApiMocks.getInvoice).toHaveBeenCalledTimes(1);
        expect(screen.getByText('Supplier A')).toBeInTheDocument();
        expect(screen.getByText('USD')).toBeInTheDocument();
    });

    it('loads a different query source once in the same mounted payment form', async () => {
        invoiceApiMocks.getInvoice
            .mockResolvedValueOnce(invoice(42, 'SI-42', '100.000000'))
            .mockResolvedValueOnce(invoice(43, 'SI-43', '200.000000'));

        render(
            <TestRouter initialEntries={['/purchase/payments/create?invoice_id=42']}>
                <Routes>
                    <Route path="/purchase/payments/create" element={<PaymentQueryHarness />} />
                </Routes>
            </TestRouter>,
        );

        expect(await screen.findByText('Loaded supplier invoice SI-42.')).toBeInTheDocument();
        await userEvent.click(screen.getByRole('button', { name: 'Load invoice 43' }));

        expect(await screen.findByText('Loaded supplier invoice SI-43.')).toBeInTheDocument();
        expect(invoiceApiMocks.getInvoice).toHaveBeenCalledTimes(2);
    });

    it('ignores stale payment source responses after a different query source is selected', async () => {
        const first = deferred<Invoice>();
        invoiceApiMocks.getInvoice
            .mockReturnValueOnce(first.promise)
            .mockResolvedValueOnce(invoice(43, 'SI-43', '200.000000'));

        render(
            <TestRouter initialEntries={['/purchase/payments/create?invoice_id=42']}>
                <Routes>
                    <Route path="/purchase/payments/create" element={<PaymentQueryHarness />} />
                </Routes>
            </TestRouter>,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Load invoice 43' }));
        expect(await screen.findByText('Loaded supplier invoice SI-43.')).toBeInTheDocument();

        await act(async () => {
            first.resolve(invoice(42, 'SI-42', '100.000000'));
            await first.promise;
        });

        expect(screen.queryByText('Loaded supplier invoice SI-42.')).not.toBeInTheDocument();
        expect(screen.getByText('Loaded supplier invoice SI-43.')).toBeInTheDocument();
    });
});

function PaymentQueryHarness() {
    const navigate = useNavigate();

    return (
        <>
            <button type="button" onClick={() => navigate('/purchase/payments/create?invoice_id=43')}>Load invoice 43</button>
            <PurchasePaymentCreateForm />
        </>
    );
}

function invoice(id = 42, invoiceNumber = 'SI-42', balance = '100.000000'): Invoice {
    return {
        id,
        row_version: 1,
        invoice_number: invoiceNumber,
        invoice_date: '2026-06-18',
        invoice_type: 'purchase',
        direction: 'inbound',
        status: 'posted',
        party: { id: 11, name: 'Supplier A' },
        currency: { id: 5, code: 'USD', name: 'USD' },
        subtotal: balance,
        grand_total: balance,
        paid_amount: '0.000000',
        balance_due: balance,
    } as Invoice;
}

function collection<T>(data: T[]): ApiCollection<T> {
    return {
        data,
        links: {},
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 2, path: '/', per_page: 10, to: data.length || null, total: 2 },
    };
}

function deferred<T>() {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;
    const promise = new Promise<T>((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return { promise, resolve, reject };
}
