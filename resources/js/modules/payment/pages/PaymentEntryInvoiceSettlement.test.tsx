import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PaymentEntryPage from './PaymentEntryPage';

const invoiceApiMocks = vi.hoisted(() => ({
    getInvoice: vi.fn(),
    getInvoiceSignedPrintLink: vi.fn(),
}));
const paymentApiMocks = vi.hoisted(() => ({
    createPayment: vi.fn(),
    finalizeSellingPayment: vi.fn(),
    listUsablePaymentMethods: vi.fn(),
}));

vi.mock('@/modules/invoice/invoiceApi', () => invoiceApiMocks);
vi.mock('../paymentApi', () => paymentApiMocks);
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: () => <div>Customer lookup</div>,
}));
vi.mock('@/modules/supplier/components/SupplierLookupSelect', () => ({
    SupplierLookupSelect: () => <div>Supplier lookup</div>,
}));
vi.mock('../components/PaymentLineTable', async (importOriginal) => ({
    ...await importOriginal<typeof import('../components/PaymentLineTable')>(),
    PaymentLineTable: ({
        lines,
        total,
        onLineChange,
    }: {
        lines: Array<{ key: number; amount: string }>;
        total: string;
        onLineChange: (key: number, patch: { paymentMethodId: string }) => void;
    }) => (
        <div>
            <div data-testid="payment-total">Payment total {total}</div>
            <button
                type="button"
                onClick={() => onLineChange(lines[0].key, { paymentMethodId: '9' })}
            >
                Select cash method
            </button>
        </div>
    ),
}));
vi.mock('@/shared/components/ContentHeader', () => ({
    ContentHeader: ({ title }: { title: ReactNode }) => <h1>{title}</h1>,
}));

const settlementCases = [
    {
        invoiceDirection: 'outbound',
        partyType: 'customer',
        partyName: 'Metro Logistics',
        paymentType: 'customer_receipt',
        paymentDirection: 'inbound',
        buttonName: 'Create customer receipt',
    },
    {
        invoiceDirection: 'inbound',
        partyType: 'supplier',
        partyName: 'Acme Supplies',
        paymentType: 'supplier_payment',
        paymentDirection: 'outbound',
        buttonName: 'Create supplier payment',
    },
] as const;

describe('Payment invoice settlement entry', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        paymentApiMocks.listUsablePaymentMethods.mockResolvedValue({
            data: [{
                id: 9,
                name: 'Cash',
                method_type: 'cash',
                requires_reference: false,
                requires_instrument_details: false,
            }],
            links: {},
            meta: {
                current_page: 1,
                from: 1,
                last_page: 1,
                per_page: 100,
                to: 1,
                total: 1,
            },
        });
        paymentApiMocks.createPayment.mockResolvedValue({ id: 801 });
        paymentApiMocks.finalizeSellingPayment.mockResolvedValue({ id: 802, payment_number: 'PAY-802', total_amount: '1310.000000' });
        invoiceApiMocks.getInvoiceSignedPrintLink.mockResolvedValue({ pdf_url: null });
    });

    it.each(settlementCases)(
        'maps a $invoiceDirection invoice to its authoritative payment direction',
        async ({
            invoiceDirection,
            partyType,
            partyName,
            paymentType,
            paymentDirection,
            buttonName,
        }) => {
            invoiceApiMocks.getInvoice.mockResolvedValue({
                id: 44,
                row_version: 6,
                invoice_number: 'INV-0044',
                invoice_type: 'manual',
                direction: invoiceDirection,
                status: 'posted',
                party_type: partyType,
                party: { id: 71, code: 'PTY-071', name: partyName },
                currency: { id: 3, code: 'LKR', name: 'Sri Lankan Rupee' },
                balance_due: '25000.000000',
            });

            render(
                <MemoryRouter initialEntries={['/payments/create?invoice_id=44']}>
                    <PaymentEntryPage />
                </MemoryRouter>,
            );

            await waitFor(() => {
                expect(screen.getByTestId('payment-total')).toHaveTextContent('25000.000000');
                expect(screen.getAllByText(partyName).length).toBeGreaterThanOrEqual(2);
            });

            await userEvent.type(screen.getByLabelText(/Exchange rate/), '1');
            await userEvent.click(screen.getByRole('button', { name: 'Select cash method' }));
            const submitButton = screen.getByRole('button', { name: buttonName });
            await waitFor(() => expect(submitButton).toBeEnabled());
            await userEvent.click(submitButton);

            await waitFor(() => expect(paymentApiMocks.createPayment).toHaveBeenCalledWith(
                expect.objectContaining({
                    payment_type: paymentType,
                    direction: paymentDirection,
                    party_type: partyType,
                    party_id: 71,
                    currency_id: 3,
                    exchange_rate: '1',
                    lines: [expect.objectContaining({
                        payment_method_id: 9,
                        amount: '25000.000000',
                        instrument_direction: paymentDirection === 'outbound' ? 'issued' : 'received',
                    })],
                    allocations: [expect.objectContaining({
                        invoice_id: 44,
                        allocated_amount: '25000.000000',
                        allocation_method: 'specific_invoice',
                        allocation_date: expect.any(String),
                    })],
                }),
            ));
        },
    );

    it.each([
        { invoiceRate: '1.000000', paymentRate: '1.000000', currency: 'LKR' },
        { invoiceRate: '355.000000', paymentRate: '360.000000', currency: 'USD' },
    ])('finalizes a selling invoice with the $currency exchange rate', async ({ invoiceRate, paymentRate, currency }) => {
        invoiceApiMocks.getInvoice.mockResolvedValue({
            id: 805,
            row_version: 1,
            invoice_number: 'SALES-2026-000001',
            invoice_type: 'sales',
            direction: 'outbound',
            status: 'posted',
            party_type: 'customer',
            party: { id: 71, name: 'Direct Customer' },
            currency: { id: 3, code: currency },
            exchange_rate: invoiceRate,
            grand_total: '1310.000000',
            balance_due: '1310.000000',
        });

        render(
            <MemoryRouter initialEntries={['/payments/create?invoice_id=805&view=selling']}>
                <PaymentEntryPage />
            </MemoryRouter>,
        );

        const cashReceived = await screen.findByLabelText('Cash received');
        fireEvent.change(cashReceived, { target: { value: '1310.00' } });

        const rate = screen.getByLabelText(`${currency} to base currency`);
        expect(rate).toHaveValue(invoiceRate);
        if (paymentRate !== invoiceRate) {
            expect(rate.closest('details')).toHaveAttribute('open');
            fireEvent.change(rate, { target: { value: paymentRate } });
        }

        const finalize = screen.getByRole('button', { name: 'Finalize payment' });
        await waitFor(() => expect(finalize).toBeEnabled());
        await userEvent.click(finalize);

        await waitFor(() => expect(paymentApiMocks.finalizeSellingPayment).toHaveBeenCalledWith(
            expect.objectContaining({
                currency_id: 3,
                exchange_rate: paymentRate,
                party_id: 71,
                lines: [expect.objectContaining({ payment_method_id: 9, amount: '1310.000000' })],
                allocations: [expect.objectContaining({ invoice_id: 805, allocated_amount: '1310.000000' })],
            }),
        ));
    });
});
