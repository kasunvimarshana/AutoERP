import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import RunningChartRegisterPage from './RunningChartRegisterPage';
import { listChartRegister } from './runningChartApi';
import { RunningChartStatus, type ChartRegisterRow } from './runningCharts';
vi.mock('./runningChartApi', () => ({ listChartRegister: vi.fn(), chartHistory: vi.fn() }));
const row: ChartRegisterRow = {
    id: 9, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized,
    starts_at: '2026-09-07T09:00:00+05:30', ends_at: '2026-09-07T17:00:00+05:30',
    start_odometer: null, end_odometer: '150.000000', total_km: null, garage_km: '0.000000', commercial_km: null,
    normal_ot_minutes: null, double_ot_minutes: null, triple_ot_minutes: null, night_outs: null, ac_mode: null, notes: null, driver_observation: null,
    vehicle_use: { id: 12, vehicle_label: 'CAR-1234', version: 2 }, customer_agreement: { reference: 'CUSTOMER-A', party_name: 'Example Customer' }, owner_agreement: null,
    corrects_chart: { id: 8, reference: 'CHART-ORIGINAL' }, replaces_vehicle: 'CAR-OLD',
};
beforeEach(() => { vi.clearAllMocks(); vi.mocked(listChartRegister).mockResolvedValue({ data: [row] }); });
describe('Running Chart register', () => {
    it('shows readable context, correction lineage and preserved unknown measurements', async () => {
        render(<RunningChartRegisterPage />);
        expect(await screen.findByText('CHART-A · CAR-1234')).toBeInTheDocument();
        expect(screen.getByText('Customer: Example Customer · CUSTOMER-A')).toBeInTheDocument();
        expect(screen.getByText('Total distance: Not recorded')).toBeInTheDocument();
        expect(screen.getByText('Corrects chart CHART-ORIGINAL')).toBeInTheDocument();
        expect(screen.getByText('Replaces vehicle CAR-OLD')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Review CHART-A' }));
        expect(screen.getByText('0.000000')).toBeInTheDocument();
        expect(screen.getByText('Driver observation: Not recorded')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Finalize usage' })).not.toBeInTheDocument();
    });
    it('applies explicit filters and reports an empty result', async () => {
        render(<RunningChartRegisterPage />); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockResolvedValue({ data: [] });
        fireEvent.change(screen.getByLabelText('Chart, vehicle, agreement or party'), { target: { value: ' SEARCH ' } });
        fireEvent.change(screen.getByLabelText('Chart status'), { target: { value: RunningChartStatus.Reversed } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('No Running Charts match these filters.')).toBeInTheDocument();
        await waitFor(() => expect(listChartRegister).toHaveBeenLastCalledWith({ search: 'SEARCH', chart_status: RunningChartStatus.Reversed, from: undefined, until: undefined }, 1, expect.any(AbortSignal)));
    });
    it('does not display old results as matching after a failed filter request', async () => {
        render(<RunningChartRegisterPage />); await screen.findByText('CHART-A · CAR-1234');
        vi.mocked(listChartRegister).mockRejectedValue(new ApiError('Period end must follow its start.', 422));
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('Period end must follow its start.')).toBeInTheDocument();
        expect(screen.queryByText('CHART-A · CAR-1234')).not.toBeInTheDocument();
    });
});
