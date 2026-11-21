import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { ApiError } from '@/shared/api/apiError';
import { configureBusinessTimeZone, formatBusinessDateTime } from '@/shared/utils/businessDate';
import RunningChartRegisterPage from './RunningChartRegisterPage';
import { listChartRegister, listCharts } from './runningChartApi';
import { listVehicleUseRegister } from './vehicleUseApi';
import { VehicleUseStatus, type VehicleUse } from './vehicleUse';
import { CHART_PERMISSION, DriverIdentitySource, RunningChartStatus, type ChartRegisterRow } from './runningCharts';
const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./runningChartApi', () => ({ listChartRegister: vi.fn(), chartHistory: vi.fn(), listCharts: vi.fn(), exportRunningChartReport: vi.fn(), transitionChart: vi.fn() }));
vi.mock('./vehicleUseApi', () => ({ getVehicleUse: vi.fn(), listVehicleUseRegister: vi.fn() }));
const row: ChartRegisterRow = {
    id: 9, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized,
    starts_at: '2026-09-07T09:00:00+05:30', ends_at: '2026-09-07T17:00:00+05:30',
    start_odometer: null, end_odometer: '150.000000', total_km: null, garage_km: '0.000000', commercial_km: null,
    normal_ot_minutes: null, double_ot_minutes: null, triple_ot_minutes: null, night_outs: null, ac_mode: null, notes: null, driver_observation: 'Signed chart received',
    driver_identity_source: DriverIdentitySource.Employee, driver_employee_id: 27, driver_name_snapshot: 'Nimal Perera', driver_reference_snapshot: 'DRV-027',
    driver: { source: DriverIdentitySource.Employee, employee_id: 27, name: 'Nimal Perera', reference: 'DRV-027' },
    vehicle_use: { id: 12, vehicle_label: 'CAR-1234', version: 2 }, customer_agreement: { reference: 'CUSTOMER-A', party_name: 'Example Customer' },
    owner_agreement: { reference: 'OWNER-A', party_name: 'Example Owner' },
    corrects_chart: { id: 8, reference: 'CHART-ORIGINAL' }, corrected_by_chart: { id: 10, reference: 'CHART-CORRECTION' }, replaces_vehicle: 'CAR-OLD',
};
const renderPage = () => render(<MemoryRouter><RunningChartRegisterPage /></MemoryRouter>);
beforeEach(() => { configureBusinessTimeZone('Asia/Colombo'); vi.clearAllMocks(); session.permissions = []; vi.mocked(listChartRegister).mockResolvedValue({ data: [row] }); });
afterEach(() => configureBusinessTimeZone(null));
describe('Running Chart register', () => {
    it('shows readable context, driver identity, correction lineage and preserved unknown measurements', async () => {
        renderPage();
        expect(screen.queryByRole('main')).not.toBeInTheDocument();
        expect(await screen.findByText('CHART-A · CAR-1234')).toBeInTheDocument();
        expect(screen.getByText(/Customer: Example Customer/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'CUSTOMER-A' })).toHaveAttribute('href', '/vehicle-rental/customer/agreements?search=CUSTOMER-A');
        expect(screen.getByText(/Owner: Example Owner/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'OWNER-A' })).toHaveAttribute('href', '/vehicle-rental/owner/agreements?search=OWNER-A');
        expect(screen.getByText('Driver: Nimal Perera · DRV-027')).toBeInTheDocument();
        expect(screen.getByText('Total distance: Not recorded')).toBeInTheDocument();
        expect(screen.getByText(`${formatBusinessDateTime(row.starts_at)} — ${formatBusinessDateTime(row.ends_at)}`)).toBeInTheDocument();
        expect(screen.getByText('Corrects chart CHART-ORIGINAL')).toBeInTheDocument();
        expect(screen.getByText('Corrected by chart CHART-CORRECTION')).toBeInTheDocument();
        expect(screen.getByText('Replaces vehicle CAR-OLD')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Review CHART-A' }));
        expect(screen.getByText('Garage distance (km)').parentElement).toHaveTextContent('0');
        expect(screen.getByText('Authoritative driver: Nimal Perera (employee) · DRV-027')).toBeInTheDocument();
        expect(screen.getByText('Driver observation: Signed chart received')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Finalize usage' })).not.toBeInTheDocument();
    });
    it('applies explicit filters and reports an empty result', async () => {
        renderPage(); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockResolvedValue({ data: [] });
        fireEvent.change(screen.getByLabelText('Chart, vehicle, driver, agreement or party'), { target: { value: ' SEARCH ' } });
        fireEvent.change(screen.getByLabelText('Chart status'), { target: { value: RunningChartStatus.Reversed } });
        const startInput = screen.getByLabelText('Period start (optional)');
        const endInput = screen.getByLabelText('Period end (optional)');
        fireEvent.change(startInput, { target: { value: '2026-09-07T09:00' } });
        expect(endInput).not.toHaveAttribute('min');
        fireEvent.change(endInput, { target: { value: '2026-09-08T09:00' } });
        expect(startInput).not.toHaveAttribute('max');
        fireEvent.change(startInput, { target: { value: '' } });
        fireEvent.change(endInput, { target: { value: '' } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('No Running Charts match these filters.')).toBeInTheDocument();
        await waitFor(() => expect(listChartRegister).toHaveBeenLastCalledWith({ search: 'SEARCH', chart_status: RunningChartStatus.Reversed, from: undefined, until: undefined }, 1, expect.any(AbortSignal)));
    });
    it('retries an unfiltered request when Clear filters follows an error', async () => {
        renderPage(); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockRejectedValueOnce(new ApiError('Running Charts could not be loaded.', 500));
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('Running Charts could not be loaded.')).toBeInTheDocument();
        vi.mocked(listChartRegister).mockResolvedValueOnce({ data: [row] });
        fireEvent.click(screen.getByRole('button', { name: 'Clear filters' }));
        await waitFor(() => expect(listChartRegister).toHaveBeenCalledTimes(3));
        expect(await screen.findByText('CHART-A · CAR-1234')).toBeInTheDocument();
    });
    it('does not display old results as matching after a failed filter request', async () => {
        renderPage(); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockRejectedValue(new ApiError('Period end must follow its start.', 422));
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('Period end must follow its start.')).toBeInTheDocument();
        expect(screen.queryByText('CHART-A · CAR-1234')).not.toBeInTheDocument();
    });
    it('clears applied filters and reloads the unfiltered chart register', async () => {
        renderPage(); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockResolvedValueOnce({ data: [] }).mockResolvedValueOnce({ data: [row] });
        fireEvent.change(screen.getByLabelText('Chart status'), { target: { value: RunningChartStatus.Reversed } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        await screen.findByText('No Running Charts match these filters.');
        fireEvent.click(screen.getByRole('button', { name: 'Clear filters' }));
        expect(await screen.findByText('CHART-A · CAR-1234')).toBeInTheDocument();
        expect(listChartRegister).toHaveBeenLastCalledWith({}, 1, expect.any(AbortSignal));
    });
    it('syncs url search params when filters are applied and cleared', async () => {
        const replaceSpy = vi.spyOn(window.history, 'replaceState');
        renderPage();
        await screen.findByText('CHART-A · CAR-1234');

        fireEvent.change(screen.getByLabelText('Chart, vehicle, driver, agreement or party'), { target: { value: 'RC-001' } });
        fireEvent.change(screen.getByLabelText('Chart status'), { target: { value: RunningChartStatus.Draft } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));

        expect(replaceSpy).toHaveBeenCalledWith({}, '', expect.stringContaining('search=RC-001'));
        expect(replaceSpy).toHaveBeenCalledWith({}, '', expect.stringContaining('status=draft'));

        await waitFor(() => expect(screen.getByRole('button', { name: 'Clear filters' })).toBeEnabled());
        fireEvent.click(screen.getByRole('button', { name: 'Clear filters' }));
        expect(replaceSpy).toHaveBeenLastCalledWith({}, '', expect.not.stringContaining('search=RC-001'));
        replaceSpy.mockRestore();
    });
    it('shows direct CRUD actions and owner sheet print when user has permissions', async () => {
        session.permissions = [CHART_PERMISSION.manage, CHART_PERMISSION.finalize, CHART_PERMISSION.reverse];
        const draftRow: ChartRegisterRow = {
            ...row,
            id: 15,
            reference: 'RC-DRAFT-01',
            status: RunningChartStatus.Draft,
            starts_at: '2026-09-07T09:00:00+05:30',
            ends_at: '2026-09-07T12:00:00+05:30',
            vehicle_use: { id: 12, vehicle_label: 'CAR-1234', version: 2 },
            customer_agreement: { id: 3, reference: 'CUST-01', party_name: 'Customer One' },
            owner_agreement: { id: 5, reference: 'OWNER-01', party_name: 'Owner One' },
        };
        vi.mocked(listChartRegister).mockResolvedValue({ data: [draftRow] });
        renderPage();

        expect(await screen.findByText('RC-DRAFT-01 · CAR-1234')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Record Running Chart' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Edit draft' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Finalize usage' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Reassign driver' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Print Sheet' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Print Owner Sheet' })).toBeInTheDocument();
    });
    it('opens quick vehicle dispatch selector with status filtering and prefilled chart editor', async () => {
        session.permissions = [CHART_PERMISSION.manage];
        const mockUse: VehicleUse = {
            id: 42,
            row_version: 3,
            status: VehicleUseStatus.InCustody,
            starts_at: '2026-09-07T09:00:00+05:30',
            ends_at: '2026-09-15T18:00:00+05:30',
            handed_over_at: '2026-09-07T09:15:00+05:30',
            returned_at: null,
            handover_odometer: '1200.000000',
            return_odometer: null,
            notes: null,
            vehicle: { id: 7, label: 'WP-TEST-7777' },
            customer_agreement: { id: 1, reference: 'CUST-AG-1', party_name: 'Test Customer', version: 1 },
            owner_agreement: { id: 2, reference: 'OWN-AG-2', party_name: 'Test Owner', version: 1 },
            replaces_use: null,
            replaced_by_use: null,
        };

        vi.mocked(listVehicleUseRegister).mockResolvedValue({
            data: [mockUse],
            meta: { current_page: 1, per_page: 25, total: 1, last_page: 1, from: 1, to: 1 },
        } as never);

        vi.mocked(listCharts).mockResolvedValue({
            data: [{
                id: 101,
                row_version: 1,
                reference: 'RC-PREV-01',
                status: RunningChartStatus.Finalized,
                starts_at: '2026-09-07T10:00:00+05:30',
                ends_at: '2026-09-07T14:00:00+05:30',
                start_odometer: '1200.000000',
                end_odometer: '1350.000000',
                garage_km: null,
                commercial_km: null,
                total_km: '150.000000',
                normal_ot_minutes: null,
                double_ot_minutes: null,
                triple_ot_minutes: null,
                night_outs: null,
                ac_mode: null,
                driver_identity_source: DriverIdentitySource.Employee,
                driver_employee_id: 14,
                driver_name_snapshot: 'Kamal Silva',
                driver_reference_snapshot: 'EMP-014',
                driver_observation: null,
                notes: null,
                driver: { source: DriverIdentitySource.Employee, employee_id: 14, name: 'Kamal Silva', reference: 'EMP-014' },
                corrects_chart: null,
                corrected_by_chart: null,
            }],
            meta: { current_page: 1, per_page: 1, total: 1, last_page: 1, from: 1, to: 1 },
        } as never);

        renderPage();
        await screen.findByText('CHART-A · CAR-1234');

        fireEvent.click(screen.getByRole('button', { name: 'Record Running Chart' }));

        expect(await screen.findByText('Select vehicle dispatch for new running chart')).toBeInTheDocument();
        expect(screen.getByLabelText('Search vehicle, customer, or agreement')).toBeInTheDocument();
        expect(screen.getByLabelText('Dispatch status')).toBeInTheDocument();

        // Switch dispatch status to returned
        fireEvent.change(screen.getByLabelText('Dispatch status'), { target: { value: 'returned' } });
        await waitFor(() => {
            expect(listVehicleUseRegister).toHaveBeenCalledWith({ use_status: 'returned', search: undefined }, 1);
        });

        // Switch back and proceed with mockUse
        fireEvent.change(screen.getByLabelText('Dispatch status'), { target: { value: 'in_custody' } });
        await screen.findByRole('button', { name: 'Proceed with selected vehicle' });

        fireEvent.click(screen.getByRole('button', { name: 'Proceed with selected vehicle' }));

        // RunningChartEditor opens with prefilled start odometer
        expect(await screen.findByRole('heading', { name: 'Record Running Chart' })).toBeInTheDocument();
        expect(screen.getByLabelText('Start odometer')).toHaveValue('1350.000000');
        expect(screen.getByLabelText('Usage start')).toHaveValue('2026-09-07T14:00');
    });
});
