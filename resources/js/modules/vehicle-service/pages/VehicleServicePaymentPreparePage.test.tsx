import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleServicePaymentPreparePage from './VehicleServicePaymentPreparePage';

const apiMocks = vi.hoisted(() => ({
    createVehicleServicePayment: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    getVehicleServicePaymentOptions: vi.fn(),
    prepareVehicleServicePayment: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

describe('VehicleServicePaymentPreparePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getVehicleServiceJob.mockResolvedValue(job());
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            methods: [{
                id: 3,
                code: 'CASH',
                name: 'Cash',
                method_type: 'cash',
                requires_reference: false,
                requires_bank_account: false,
            }],
            bank_accounts: [],
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
            status: 'posted',
            posting_status: 'posted',
            allocation_status: 'fully_allocated',
        });
    });

    it('requires and submits a configured payment method', async () => {
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '11');
        await user.selectOptions(selects[1], '3');
        await user.click(screen.getByRole('button', { name: 'Receive, post and allocate' }));

        await waitFor(() => expect(apiMocks.createVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            invoice_id: 11,
            payment_method_id: 3,
            amount: '100.000000',
        })));
        expect(await screen.findByText('Payment created')).toBeInTheDocument();
    });

    it('supports payment methods that require a reference and bank account', async () => {
        apiMocks.getVehicleServicePaymentOptions.mockResolvedValue({
            methods: [{
                id: 4,
                code: 'BANK',
                name: 'Bank Transfer',
                method_type: 'bank_transfer',
                requires_reference: true,
                requires_bank_account: true,
            }],
            bank_accounts: [{ id: 7, code: 'BANK-001', name: 'Main Bank' }],
        });
        const user = userEvent.setup();
        renderPage();

        expect(await screen.findByText('Payment for JOB-1')).toBeInTheDocument();
        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '11');
        await user.selectOptions(selects[1], '4');
        await user.selectOptions(screen.getByLabelText('Internal bank account'), '7');
        await user.type(screen.getByLabelText('Reference *'), 'TRX-100');
        await user.click(screen.getByRole('button', { name: 'Review payment' }));

        await waitFor(() => expect(apiMocks.prepareVehicleServicePayment).toHaveBeenCalledWith(9, expect.objectContaining({
            payment_method_id: 4,
            internal_bank_account_id: 7,
            reference_number: 'TRX-100',
        })));
        expect(await screen.findByText('Payment validation completed successfully')).toBeInTheDocument();
    });
});

function renderPage() {
    return render(
        <MemoryRouter initialEntries={['/vehicle-service/jobs/9/payment']}>
            <Routes>
                <Route path="/vehicle-service/jobs/:id/payment" element={<VehicleServicePaymentPreparePage />} />
                <Route path="/payments/:id" element={<div>Payment created</div>} />
            </Routes>
        </MemoryRouter>,
    );
}

function job() {
    return {
        id: 9,
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
