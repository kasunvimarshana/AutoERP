import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import VehicleServiceHistoryReportPage from './VehicleServiceHistoryReportPage';

const apiMocks = vi.hoisted(() => ({ runVehicleServiceHistoryReport: vi.fn() }));
vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({ ExportActions: () => null }));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({ VehicleLookupSelect: () => <div>Vehicle search</div> }));

const record = {
    id: 'current:8', live_job_id: 8, source: 'current', source_label: 'Current System',
    job_type: 'full_service', job_type_label: 'Full Service', job_number: 'JOB-0008',
    job_date: '2026-10-25', status: 'invoiced', vehicle_label: 'ABC-1234', customer: null,
    customer_name: 'Nimal', supervisor: null, supervisor_name: '', technicians: [], technician_names: '',
    odometer_reading: '10000', next_service_mileage: null, complaint: 'Oil change', inspection_notes: null,
    diagnosis: null, recommended_work: null, work_summary: 'Oil change',
    lines: [{ id: 11, category: 'work', description: 'Oil change', quantity: '1.000000', uom: null,
        unit_price: '2000.000000', discount_amount: '200.000000', tax_amount: '90.000000',
        charge_amount: '10.000000', line_total: '1900.000000', is_billable: true, status: 'completed', employees: [] }],
    job_discount_amount: '100.000000', job_total: '1800.000000', invoices: [
        { id: 41, number: 'SALES-2026-000041', status: 'posted' },
        { id: 42, number: 'SALES-2026-000042', status: 'partially_paid' },
    ],
    invoice_total: '1800.000000', paid_total: '1000.000000', balance_due: '800.000000',
};

describe('VehicleServiceHistoryReportPage', () => {
    it('shows current job prices and every linked invoice, while old records remain without prices', async () => {
        apiMocks.runVehicleServiceHistoryReport.mockResolvedValue({
            data: [record, { ...record, id: 'legacy:5', live_job_id: null, source: 'legacy', source_label: 'Old System',
                job_number: 'OLD-0005', lines: [{ id: 12, category: 'part', description: 'Old filter', quantity: '1.000000', uom: 'PCS', employees: [] }],
                job_discount_amount: null, job_total: null, invoices: [] }],
            vehicle: null,
            summary: { total_jobs: 2, last_service_date: null, last_service_mileage: null, next_service_mileage: null },
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 10, to: 2, total: 2 },
        });
        const user = userEvent.setup();
        render(<TestRouter><VehicleServiceHistoryReportPage /></TestRouter>);

        await user.click(await screen.findByText('View full service record'));
        const current = screen.getByText('JOB-0008').closest('article')!;
        expect(within(current).getByRole('link', { name: /SALES-2026-000041/ })).toHaveAttribute('href', '/invoices/41');
        expect(within(current).getByRole('link', { name: /SALES-2026-000042/ })).toHaveAttribute('href', '/invoices/42');
        expect(within(current).getByRole('columnheader', { name: 'Unit price' })).toBeInTheDocument();
        expect(within(current).getByText('Tax LKR 90.00 · Charges LKR 10.00')).toBeInTheDocument();
        expect(within(current).getByText('−LKR 100.00')).toBeInTheDocument();
        expect(within(current).getByRole('row', { name: /Oil change/ })).toHaveTextContent('LKR 2,000.00');
        expect(within(current).getByRole('row', { name: /Oil change/ })).toHaveTextContent('LKR 200.00');
        expect(within(current).getByRole('row', { name: /Oil change/ })).toHaveTextContent('LKR 1,900.00');

        await user.click(screen.getByText('View old service items'));
        const legacy = screen.getByText('OLD-0005').closest('article')!;
        expect(within(legacy).getByText('Old filter')).toBeInTheDocument();
        expect(within(legacy).queryByRole('columnheader', { name: 'Unit price' })).not.toBeInTheDocument();
        expect(within(legacy).queryByText('Invoices')).not.toBeInTheDocument();
    });
});
