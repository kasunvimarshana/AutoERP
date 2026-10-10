import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleServicePaymentPreparePage from './VehicleServicePaymentPreparePage';

const apiMocks = vi.hoisted(() => ({
    createVehicleServicePayment: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    getVehicleServicePaymentOptions: vi.fn(),
}));

const invoiceApiMocks = vi.hoisted(() => ({
    getInvoiceSignedPrintLink: vi.fn(),
}));

const navigationMocks = vi.hoisted(() => ({
    openSameOriginUrl: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);
vi.mock('@/modules/invoice/invoiceApi', () => invoiceApiMocks);
vi.mock('@/shared/utils/safeNavigation', () => navigationMocks);

describe('VehicleServicePaymentPreparePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getVehicleServiceJob.mockResolvedValue(job());
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: false,
            methods: [{
                id: 3,
                code: 'CASH',
                name: 'Cash',
                method_type: 'cash',
                requires_reference: false,
                requires_instrument_details: false,
            }],
        });
        apiMocks.createVehicleServicePayment.mockResolvedValue({
            id: 99,
            payment_number: 'PAY-99',
            document_status: 'approved',
            posting_status: 'posted',
            allocation_status: 'fully_allocated',
        });
        invoiceApiMocks.getInvoiceSignedPrintLink.mockResolvedValue({
            print_url: '/signed/invoices/11/print',
            pdf_url: '/signed/invoices/11/pdf',
        });
    });

    it('submits the exact job version with the selected payment method', async () => {
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        await user.type(screen.getByLabelText('Cash received'), '100');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            expected_version: 7,
            invoice_id: 11,
            lines: [expect.objectContaining({
                payment_method_id: 3,
                reference_number: undefined,
            })],
        })));
        expect(await screen.findByText('Payment created and allocated successfully')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Print bill' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Finalize payment' })).not.toBeInTheDocument();
    });

    it('uses transaction instrument details without exposing an internal Finance account', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: false,
            methods: [{
                id: 4,
                code: 'BANK',
                name: 'Bank Transfer',
                method_type: 'bank_transfer',
                requires_reference: true,
                requires_instrument_details: true,
            }],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        expect(screen.queryByLabelText('Internal bank account')).not.toBeInTheDocument();
        await user.type(screen.getByLabelText('Reference details'), 'TRX-100');
        await user.click(screen.getByText('Additional transaction details'));
        await user.type(screen.getByLabelText('External bank'), 'Customer Bank');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            expected_version: 7,
            lines: [expect.objectContaining({
                payment_method_id: 4,
                reference_number: 'TRX-100',
                instrument_number: 'TRX-100',
                external_bank_name: 'Customer Bank',
            })],
        })));
    });

    it('prints the settled invoice from the same page after payment completion', async () => {
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        await user.type(screen.getByLabelText('Cash received'), '100');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await screen.findByText('Payment created and allocated successfully');
        await user.click(screen.getByRole('button', { name: 'Print bill' }));

        await waitFor(() => expect(invoiceApiMocks.getInvoiceSignedPrintLink).toHaveBeenCalledWith(11));
        expect(navigationMocks.openSameOriginUrl).toHaveBeenCalledWith('/signed/invoices/11/print');
    });

    it('calculates cash change in the UI and submits only the amount applied to the invoice', async () => {
        apiMocks.getVehicleServiceJob.mockResolvedValue(job('1000'));
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        const receivedField = screen.getByLabelText('Cash received');
        await user.type(receivedField, '500');
        expect(screen.getByText('Cash amount applied').parentElement).toHaveTextContent(/500\.00/);
        expect(screen.getByText('Change to return').parentElement).toHaveTextContent(/0\.00/);

        await user.clear(receivedField);
        await user.type(receivedField, '5000');

        expect(screen.getAllByText(/1,000\.00/).length).toBeGreaterThan(0);
        expect(screen.getByText(/4,000\.00/)).toBeInTheDocument();
        expect(screen.queryByText(/5,000\.00/)).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));
        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            lines: [{ amount: '1000.000000', payment_method_id: 3, reference_number: undefined }],
        })));
        expect(JSON.stringify(apiMocks.createVehicleServicePayment.mock.calls[0])).not.toContain('5000');
    });

    it('shows card brands as radio choices and submits the selected brand', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: false,
            methods: [{
                id: 8,
                code: 'CARD',
                name: 'Card',
                method_type: 'card',
                requires_reference: false,
                requires_instrument_details: false,
            }],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        await user.click(screen.getByRole('radio', { name: 'VISA' }));
        await user.clear(screen.getByLabelText('Card amount'));
        await user.type(screen.getByLabelText('Card amount'), '75');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            lines: [expect.objectContaining({ amount: '75', payment_method_id: 8, card_brand: 'visa' })],
        })));
    });

    it('preserves cheque payment submission with instrument evidence', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: false,
            methods: [{
                id: 14,
                code: 'CHEQUE',
                name: 'Cheque',
                method_type: 'cheque',
                requires_reference: true,
                requires_instrument_details: true,
            }],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        expect(screen.getByLabelText('Cheque amount')).toBeInTheDocument();
        await user.click(screen.getByText('Additional transaction details'));
        await user.type(screen.getByLabelText('Cheque number'), 'CHQ-14');
        await user.type(screen.getByLabelText('External bank'), 'Issuing bank');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            lines: [expect.objectContaining({
                payment_method_id: 14,
                reference_number: 'CHQ-14',
                instrument_number: 'CHQ-14',
                external_bank_name: 'Issuing bank',
            })],
        })));
    });

    it('preserves wallet payment and its source transaction reference', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: false,
            methods: [{
                id: 15,
                code: 'WALLET',
                name: 'Mobile wallet',
                method_type: 'mobile_wallet',
                requires_reference: true,
                requires_instrument_details: true,
            }],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        await user.selectOptions(screen.getByLabelText('Invoice'), '11');
        expect(screen.getByLabelText('Wallet amount')).toBeInTheDocument();
        await user.click(screen.getByText('Additional transaction details'));
        await user.type(screen.getByLabelText('Wallet reference'), 'WLT-15');
        await user.type(screen.getByLabelText('Provider'), 'Wallet provider');
        await user.click(screen.getByRole('button', { name: 'Finalize payment' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            lines: [expect.objectContaining({
                payment_method_id: 15,
                reference_number: 'WLT-15',
                instrument_number: 'WLT-15',
                external_bank_name: 'Wallet provider',
            })],
        })));
    });

    it('offers Credit Payment only when the backend reports credit approval', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
            credit_allowed: true,
            methods: [],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByRole('radio', { name: 'Credit Payment' })).toBeInTheDocument();
        await user.click(screen.getByRole('radio', { name: 'Credit Payment' }));
        expect(screen.getByText(/will remain on this customer account/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Keep invoice on credit' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Finalize payment' })).not.toBeInTheDocument();
    });
});

function renderPage() {
    return render(
        <TestRouter initialEntries={['/vehicle-service/jobs/9/payment']}>
            <Routes>
                <Route path="/vehicle-service/jobs/:id/payment" element={<VehicleServicePaymentPreparePage />} />
            </Routes>
        </TestRouter>,
    );
}

function job(invoiceBalance = '100.000000') {
    return {
        id: 9,
        row_version: 7,
        job_number: 'JOB-1',
        job_date: '2026-06-20',
        type: 'full_service',
        customer_id: 5,
        vehicle_id: 6,
        supervisor_commission_type: 'none',
        supervisor_commission_value: '0.000000',
        supervisor_commission_amount: '0.000000',
        status: 'invoiced',
        subtotal: invoiceBalance,
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: invoiceBalance,
        invoice_links: [{
            id: 1,
            invoice_id: 11,
            invoice_number: 'INV-11',
            invoice_total: invoiceBalance,
            balance_due: invoiceBalance,
            invoice_status: 'posted',
            status: 'active',
            can_receive_payment: true,
        }],
        payment_links: [],
    };
}
