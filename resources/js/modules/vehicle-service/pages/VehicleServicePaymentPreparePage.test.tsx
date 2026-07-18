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
    prepareVehicleServicePayment: vi.fn(),
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
            methods: [{
                id: 3,
                code: 'CASH',
                name: 'Cash',
                method_type: 'cash',
                requires_reference: false,
                requires_instrument_details: false,
            }],
        });
        apiMocks.prepareVehicleServicePayment.mockResolvedValue({
            paymentType: 'service_receipt',
            direction: 'inbound',
            paymentDate: '2026-06-20',
            lines: [{ amount: '100.000000', paymentMethodId: 3 }],
            allocations: [{ invoiceId: 11, allocatedAmount: '100.000000' }],
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
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '11');
        await user.selectOptions(selects[1], '3');
        await user.click(screen.getByRole('button', { name: 'Receive, post and allocate' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            expected_version: 7,
            invoice_id: 11,
            payment_method_id: 3,
            amount: '100.000000',
        })));
        expect(await screen.findByText('Payment created and allocated successfully')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Print bill' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Receive, post and allocate' })).not.toBeInTheDocument();
    });

    it('uses transaction instrument details without exposing an internal Finance account', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            job_version: 7,
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
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '11');
        await user.selectOptions(selects[1], '4');
        expect(screen.queryByLabelText('Internal bank account')).not.toBeInTheDocument();
        await user.type(screen.getByLabelText('Transfer reference'), 'TRX-100');
        await user.type(screen.getByLabelText('External bank'), 'Customer Bank');
        await user.click(screen.getByRole('button', { name: 'Review payment' }));

        await waitFor(() => expect(apiMocks.prepareVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            expected_version: 7,
            payment_method_id: 4,
            reference_number: 'TRX-100',
            instrument_number: 'TRX-100',
            external_bank_name: 'Customer Bank',
        })));
        expect(await screen.findByText('Payment validation completed successfully')).toBeInTheDocument();
    });

    it('prints the settled invoice from the same page after payment completion', async () => {
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '11');
        await user.selectOptions(selects[1], '3');
        await user.click(screen.getByRole('button', { name: 'Receive, post and allocate' }));

        await screen.findByText('Payment created and allocated successfully');
        await user.click(screen.getByRole('button', { name: 'Print bill' }));

        await waitFor(() => expect(invoiceApiMocks.getInvoiceSignedPrintLink).toHaveBeenCalledWith(11));
        expect(navigationMocks.openSameOriginUrl).toHaveBeenCalledWith('/signed/invoices/11/print');
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

function job() {
    return {
        id: 9,
        row_version: 7,
        job_number: 'JOB-1',
        job_date: '2026-06-20',
        customer_id: 5,
        vehicle_id: 6,
        supervisor_commission_type: 'none',
        supervisor_commission_value: '0.000000',
        supervisor_commission_amount: '0.000000',
        status: 'invoiced',
        subtotal: '100.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '100.000000',
        invoice_links: [{
            id: 1,
            invoice_id: 11,
            invoice_number: 'INV-11',
            invoice_total: '100.000000',
            balance_due: '100.000000',
            invoice_status: 'posted',
            status: 'active',
            can_receive_payment: true,
        }],
        payment_links: [],
    };
}
