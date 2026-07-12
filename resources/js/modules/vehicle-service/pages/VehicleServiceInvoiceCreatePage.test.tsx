import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleServiceInvoiceCreatePage from './VehicleServiceInvoiceCreatePage';
const apiMocks = vi.hoisted(() => ({
    createVehicleServiceInvoice: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    listBillableLines: vi.fn(),
    previewVehicleServiceInvoice: vi.fn(),
}));
vi.mock('../vehicleServiceApi', () => apiMocks);
describe('VehicleServiceInvoiceCreatePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getVehicleServiceJob.mockResolvedValue(job());
        apiMocks.listBillableLines.mockResolvedValue([billableLine()]);
        apiMocks.previewVehicleServiceInvoice.mockResolvedValue({
            subtotal: '100.000000',
            discountTotal: '0.000000',
            taxTotal: '0.000000',
            chargeTotal: '0.000000',
            grandTotal: '100.000000',
        });
        apiMocks.createVehicleServiceInvoice.mockResolvedValue({
            id: 41,
            invoice_number: 'INV-41',
            status: 'posted',
            posted_at: '2026-06-20T10:00:00Z',
        });
    });
    it('creates and posts the selected service-job quantities', async () => {
        const user = userEvent.setup();
        renderPage();
        expect(await screen.findByText('Invoice JOB-1')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Create & post invoice' }));
        await waitFor(() => expect(apiMocks.createVehicleServiceInvoice).toHaveBeenCalledWith(
            9,
            expect.objectContaining({
                expected_version: 7,
                line_quantities: { 21: '1.000000' },
                exchange_rate: '1.000000',
            }),
        ));
        expect(await screen.findByText('Posted invoice')).toBeInTheDocument();
        expect(window.location.pathname).toBe('/invoices/41');
        expect(window.location.search).toBe('?from=vehicle-service&job_id=9');
    });
    it('clears an old preview when an invoice header value changes', async () => {
        const user = userEvent.setup();
        renderPage();
        expect(await screen.findByText('Invoice JOB-1')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Preview' }));
        expect(await screen.findByText('Grand total')).toBeInTheDocument();
        await user.clear(screen.getByLabelText('Notes'));
        await user.type(screen.getByLabelText('Notes'), 'Changed');
        expect(screen.queryByText('Grand total')).not.toBeInTheDocument();
    });
});
function renderPage() {
    return render(
        <TestRouter initialEntries={['/vehicle-service/jobs/9/invoice']}>
            <Routes>
                <Route path="/vehicle-service/jobs/:id/invoice" element={<VehicleServiceInvoiceCreatePage />} />
                <Route path="/invoices/:id" element={<div>Posted invoice</div>} />
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
        status: 'completed',
        subtotal: '100.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '100.000000',
        invoice_links: [],
        payment_links: [],
    };
}
function billableLine() {
    return {
        id: 21,
        line_number: 1,
        line_source_type: 'service_item',
        description: 'Service labour',
        quantity: '1.000000',
        unit_cost: '0.000000',
        unit_price: '100.000000',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
        line_total: '100.000000',
        is_inventory_tracked: false,
        is_customer_supplied: false,
        is_external: false,
        is_billable: true,
        is_employee_assignable: false,
        invoiced_quantity: '0.000000',
        remaining_billable_quantity: '1.000000',
        invoice_state: 'uninvoiced',
        status: 'completed',
    };
}
