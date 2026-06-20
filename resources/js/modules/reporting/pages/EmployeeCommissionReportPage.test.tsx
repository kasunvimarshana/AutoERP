import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import EmployeeCommissionReportPage from './EmployeeCommissionReportPage';

const apiMocks = vi.hoisted(() => ({
    runEmployeeCommissionReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: ({ reportKey }: { reportKey: string }) => <div data-testid="export-actions">{reportKey}</div>,
}));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ organizationUnit: { id: 1, name: 'Main workshop' } }),
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({ CustomerLookupSelect: () => null }));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({ VehicleLookupSelect: () => null }));
vi.mock('@/shared/components/GenericLookupSelect', () => ({ GenericLookupSelect: () => null }));
vi.mock('@/modules/hr/hrApi', () => ({
    searchDepartments: vi.fn(),
    searchDesignations: vi.fn(),
    searchEmployees: vi.fn(),
}));

describe('EmployeeCommissionReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runEmployeeCommissionReport.mockResolvedValue({
            report: {
                key: 'vehicle-service/employee-commissions',
                title: 'Employee Commission Report',
                group: 'Vehicle Service',
                description: 'Technician and supervisor commissions.',
                columns: [],
                filters: [],
                supports_date_range: true,
                default_sort: 'job_date',
                default_direction: 'desc',
            },
            data: [
                {
                    id: 'supervisor-10',
                    commission_source: 'supervisor',
                    employee: { id: 5, code: 'SUP-001', name: 'Nimal Supervisor', department: null, designation: null },
                    employee_code: 'SUP-001',
                    employee_name: 'Nimal Supervisor',
                    department: null,
                    department_name: 'Workshop',
                    designation: null,
                    designation_name: 'Supervisor',
                    job: { id: 10, code: 'JOB-001', name: 'JOB-001' },
                    job_number: 'JOB-001',
                    job_date: '2026-06-20',
                    customer: null,
                    customer_name: 'Kasun Customer',
                    vehicle: null,
                    vehicle_label: 'CAB-1234',
                    supervisor: { id: 5, code: 'SUP-001', name: 'Nimal Supervisor' },
                    supervisor_name: 'Nimal Supervisor',
                    line_description: 'Job supervision',
                    role_type: 'supervisor',
                    assigned_hours: '0.000000',
                    rate: '0.000000',
                    labour_amount: '0.000000',
                    commission_base: '1000.000000',
                    commission_type: 'percentage',
                    commission_value: '5.000000',
                    commission_amount: '50.000000',
                    commission_status: 'earned',
                    completed_at: '2026-06-20 12:00:00',
                    invoice_progress: 'invoiced',
                    payment_progress: 'partially_paid',
                    invoice_total: '1000.000000',
                    paid_total: '500.000000',
                    balance_due: '500.000000',
                    job_status: 'completed',
                    group_key: '5',
                    group_label: 'Nimal Supervisor',
                },
            ],
            summary: {
                total_entries: 1,
                total_employees: 1,
                total_jobs: 1,
                total_hours: '0.000000',
                total_labour_value: '0.000000',
                total_commission_base: '1000.000000',
                technician_commission: '0.000000',
                supervisor_commission: '50.000000',
                earned_commission: '50.000000',
                pending_commission: '0.000000',
                total_commission: '50.000000',
                average_commission_per_job: '50.000000',
                average_commission_per_employee: '50.000000',
                average_commission_per_hour: '0.000000',
            },
            rankings: {
                top_earning_employee: null,
                top_commission_employee: {
                    employee: { id: 5, code: 'SUP-001', name: 'Nimal Supervisor' },
                    labour_value: '0.000000',
                    commission_amount: '50.000000',
                },
            },
            groups: [
                {
                    key: '5',
                    label: 'Nimal Supervisor',
                    resource: { id: 5, code: '', name: 'Nimal Supervisor' },
                    total_jobs: 1,
                    total_hours: '0.000000',
                    total_labour_value: '0.000000',
                    total_commission: '50.000000',
                },
            ],
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 25, to: 1, total: 1 },
        });
    });

    it('renders technician and supervisor commission fields and applies source filters', async () => {
        const user = userEvent.setup();
        render(
            <MemoryRouter>
                <EmployeeCommissionReportPage />
            </MemoryRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Employee Commission Report' })).toBeInTheDocument();
        expect(screen.getAllByText('Nimal Supervisor').length).toBeGreaterThan(0);
        expect(screen.getAllByText(/50/).length).toBeGreaterThan(0);
        expect(screen.getByText('Supervisor commission')).toBeInTheDocument();
        expect(screen.getByTestId('export-actions')).toHaveTextContent('vehicle-service/employee-commissions');

        await user.selectOptions(screen.getByLabelText('Commission source'), 'supervisor');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));

        await waitFor(() => {
            expect(apiMocks.runEmployeeCommissionReport).toHaveBeenLastCalledWith(
                expect.objectContaining({ commission_source: 'supervisor', page: 1 }),
                expect.any(AbortSignal),
            );
        });
    });
});
