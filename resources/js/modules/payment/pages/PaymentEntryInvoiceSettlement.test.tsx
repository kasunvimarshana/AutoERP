import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PaymentEntryPage from './PaymentEntryPage';

const invoiceApiMocks = vi.hoisted(() => ({
    getInvoice: vi.fn(),
}));
const paymentApiMocks = vi.hoisted(() => ({
    createPayment: vi.fn(),
    listPaymentMethods: vi.fn(),
}));

vi.mock('@/modules/invoice/invoiceApi', () => invoiceApiMocks);
vi.mock('../paymentApi', () => paymentApiMocks);
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: () => <div>Customer lookup</div>,
}));
vi.mock('@/modules/supplier/components/SupplierLookupSelect', () => ({
    SupplierLookupSelect: () => <div>Supplier lookup</div>,
}));
vi.mock('../components/PaymentLineTable', () => ({
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
        paymentApiMocks.listPaymentMethods.mockResolvedValue({
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
});
