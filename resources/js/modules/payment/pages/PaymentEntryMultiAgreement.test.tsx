import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PaymentEntryPage from './PaymentEntryPage';

const paymentApiMocks = vi.hoisted(() => ({
    getPayment: vi.fn(),
    createPayment: vi.fn(),
    updatePayment: vi.fn(),
    finalizeSellingPayment: vi.fn(),
    listUsablePaymentMethods: vi.fn(),
}));

const agreementApiMocks = vi.hoisted(() => ({
    getPartyOpenRentalDocuments: vi.fn(),
    AgreementKind: {
        Customer: 'customer',
        Owner: 'owner',
    },
}));

vi.mock('../paymentApi', () => paymentApiMocks);
vi.mock('@/modules/vehicle-rental/agreementApi', () => agreementApiMocks);
vi.mock('@/modules/invoice/invoiceApi', () => ({
    getInvoice: vi.fn(),
    getInvoiceSignedPrintLink: vi.fn(),
    getInvoiceWhatsAppShare: vi.fn(),
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: ({ onChange }: { onChange: (p: { id: number; name: string }) => void }) => (
        <button
            type="button"
            data-testid="select-customer-btn"
            onClick={() => onChange({ id: 50, name: 'Acme Logistics' })}
        >
            Select Acme Logistics
        </button>
    ),
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
        onLineChange: (key: number, patch: { paymentMethodId: string; amount?: string }) => void;
    }) => (
        <div>
            <div data-testid="payment-total">Payment total {total}</div>
            <button
                type="button"
                onClick={() => onLineChange(lines[0].key, { paymentMethodId: '1', amount: '15000.000000' })}
            >
                Set payment amount 15000
            </button>
        </div>
    ),
}));

describe('PaymentEntryPage Multi-Agreement and Draft Edit Flow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        paymentApiMocks.listUsablePaymentMethods.mockResolvedValue({
            data: [{
                id: 1,
                name: 'Bank Transfer',
                method_type: 'bank_transfer',
                requires_reference: false,
                requires_instrument_details: false,
            }],
            links: {},
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 100, to: 1, total: 1 },
        });
        paymentApiMocks.createPayment.mockResolvedValue({ id: 999 });
        paymentApiMocks.updatePayment.mockResolvedValue({ id: 101, row_version: 2 });
    });

    it('renders edit mode for a draft payment and saves updates with expected version', async () => {
        paymentApiMocks.getPayment.mockResolvedValue({
            id: 101,
            row_version: 5,
            payment_number: 'PAY-DRAFT-101',
            document_status: 'draft',
            payment_type: 'customer_receipt',
            direction: 'inbound',
            payment_date: '2026-11-16',
            reference_number: 'REF-EDIT-001',
            exchange_rate: '1.000000',
            party: { id: 50, name: 'Acme Logistics' },
            lines: [{
                id: 201,
                payment_method_id: 1,
                payment_method: { id: 1, name: 'Bank Transfer', method_type: 'bank_transfer' },
                amount: '8000.000000',
                reference_number: 'WIRE-123',
            }],
            allocations: [],
        });

        render(
            <MemoryRouter initialEntries={['/payments/101/edit']}>
                <Routes>
                    <Route path="/payments/:id/edit" element={<PaymentEntryPage />} />
                </Routes>
            </MemoryRouter>,
        );

        expect(await screen.findByText('Edit draft payment PAY-DRAFT-101')).toBeInTheDocument();
        await waitFor(() => {
            expect(screen.getByDisplayValue('REF-EDIT-001')).toBeInTheDocument();
        });

        const submitButton = screen.getByRole('button', { name: 'Save draft payment' });
        await waitFor(() => expect(submitButton).toBeEnabled());
        await userEvent.click(submitButton);

        await waitFor(() => {
            expect(paymentApiMocks.updatePayment).toHaveBeenCalledWith(
                101,
                expect.objectContaining({
                    expected_version: 5,
                    payment_type: 'customer_receipt',
                    direction: 'inbound',
                    reference_number: 'REF-EDIT-001',
                    lines: [expect.objectContaining({
                        payment_method_id: 1,
                        amount: '8000.000000',
                    })],
                }),
            );
        });
    });

    it('loads party open rental documents across multiple agreements and tracks allocations with surplus advance', async () => {
        agreementApiMocks.getPartyOpenRentalDocuments.mockResolvedValue({
            party: { id: 50, name: 'Acme Logistics', code: 'CUST-050', kind: 'customer' },
            agreements: [
                { id: 10, reference: 'RNT-AGR-0010', status: 'active' },
                { id: 11, reference: 'RNT-AGR-0011', status: 'active' },
            ],
            summary: {
                total_open_balance: '12000.000000',
                total_documents_count: 2,
                active_agreements_count: 2,
                party_unapplied_advance: '0.000000',
            },
            documents: [
                {
                    id: 301,
                    invoice_number: 'INV-2026-0301',
                    invoice_type: 'rental',
                    direction: 'inbound',
                    status: 'posted',
                    invoice_date: '2026-11-01',
                    currency_code: 'LKR',
                    total_amount: '5000.000000',
                    remaining_amount: '5000.000000',
                    balance_due: '5000.000000',
                    agreement_id: 10,
                    agreement_reference: 'RNT-AGR-0010',
                    vehicle_registration: 'CAB-1010',
                    driver_name: 'John Driver',
                },
                {
                    id: 302,
                    invoice_number: 'INV-2026-0302',
                    invoice_type: 'rental',
                    direction: 'inbound',
                    status: 'posted',
                    invoice_date: '2026-11-05',
                    currency_code: 'LKR',
                    total_amount: '7000.000000',
                    remaining_amount: '7000.000000',
                    balance_due: '7000.000000',
                    agreement_id: 11,
                    agreement_reference: 'RNT-AGR-0011',
                    vehicle_registration: 'WP-8888',
                    driver_name: null,
                },
            ],
        });

        render(
            <MemoryRouter initialEntries={['/payments/create']}>
                <Routes>
                    <Route path="/payments/create" element={<PaymentEntryPage />} />
                </Routes>
            </MemoryRouter>,
        );

        // Select Customer
        const selectCustomerBtn = screen.getByTestId('select-customer-btn');
        fireEvent.click(selectCustomerBtn);

        // Verify open documents are fetched and displayed
        expect(await screen.findByText('Open documents & agreement allocations')).toBeInTheDocument();
        expect(await screen.findByText('RNT-AGR-0010')).toBeInTheDocument();
        expect(screen.getByText('RNT-AGR-0011')).toBeInTheDocument();
        expect(screen.getByText('INV-2026-0301')).toBeInTheDocument();
        expect(screen.getByText('INV-2026-0302')).toBeInTheDocument();

        // Set tendered payment amount to 15,000
        fireEvent.click(screen.getByRole('button', { name: 'Set payment amount 15000' }));

        // Click "Pay full" on both open documents (5,000 + 7,000 = 12,000 allocated, 3,000 surplus)
        const payFullButtons = screen.getAllByRole('button', { name: 'Pay full' });
        expect(payFullButtons).toHaveLength(2);
        fireEvent.click(payFullButtons[0]);
        fireEvent.click(payFullButtons[1]);

        // Verify summary calculations
        expect(screen.getByText('Total Allocated').parentElement).toHaveTextContent('12,000');
        expect(screen.getByText('Unapplied Advance / Surplus').parentElement).toHaveTextContent('3,000');

        // Submit multi-agreement payment
        const submitButton = screen.getByRole('button', { name: 'Create payment' });
        expect(submitButton).toBeEnabled();
        await userEvent.click(submitButton);

        await waitFor(() => {
            expect(paymentApiMocks.createPayment).toHaveBeenCalledWith(
                expect.objectContaining({
                    payment_type: 'customer_receipt',
                    direction: 'inbound',
                    party_id: 50,
                    lines: [expect.objectContaining({
                        payment_method_id: 1,
                        amount: '15000.000000',
                    })],
                    allocations: expect.arrayContaining([
                        expect.objectContaining({
                            invoice_id: 301,
                            allocated_amount: '5000.000000',
                            allocation_method: 'specific_invoice',
                        }),
                        expect.objectContaining({
                            invoice_id: 302,
                            allocated_amount: '7000.000000',
                            allocation_method: 'specific_invoice',
                        }),
                    ]),
                }),
            );
        });
    });
});
