import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { RunningChartsPanel } from './RunningChartsPanel';
import { listCharts, transitionChart } from './runningChartApi';
import { RunningChartAction, RunningChartStatus, CHART_PERMISSION, type RunningChart } from './runningCharts';
import { VehicleUseStatus, localTimestampValue, timestampWithOffset, type VehicleUse } from './vehicleUse';
const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./runningChartApi', () => ({ listCharts: vi.fn(), transitionChart: vi.fn(), chartHistory: vi.fn(), createChart: vi.fn(), updateChart: vi.fn() }));
const use = { id: 3, row_version: 2, status: VehicleUseStatus.InCustody, vehicle: { id: 9, label: 'CAR-1234' } } as VehicleUse;
const chart = { id: 5, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized, starts_at: '2026-09-07T09:00:15+05:30', ends_at: '2026-09-07T17:00:30+05:30', total_km: null, corrects_chart: null } as RunningChart;
beforeEach(() => { vi.clearAllMocks(); session.permissions = [CHART_PERMISSION.view]; vi.mocked(listCharts).mockResolvedValue({ data: [chart] }); });
describe('Running Chart review', () => {
 it('preserves unknown distance and hides unauthorized actions', async () => {
  render(<RunningChartsPanel use={use} />); expect(await screen.findByText('CHART-A · Finalized')).toBeInTheDocument(); expect(screen.getByText('Total distance: Not recorded km')).toBeInTheDocument(); expect(screen.queryByRole('button', { name: 'Reverse usage' })).not.toBeInTheDocument(); expect(screen.queryByRole('button', { name: 'Record usage' })).not.toBeInTheDocument();
 });
 it('requires a reason and preserves stale revision errors', async () => {
  session.permissions.push(CHART_PERMISSION.reverse); vi.mocked(transitionChart).mockRejectedValue(new ApiError('This record changed. Reload before continuing.', 409));
  render(<RunningChartsPanel use={use} />); fireEvent.click(await screen.findByRole('button', { name: 'Reverse usage' })); expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled(); fireEvent.change(screen.getByLabelText('Reversal reason'), { target: { value: 'Correct signed usage' } }); fireEvent.click(screen.getByRole('button', { name: 'Confirm' })); expect(await screen.findByText('This record changed. Reload before continuing.')).toBeInTheDocument(); expect(transitionChart).toHaveBeenCalledWith(chart, RunningChartAction.Reverse, 'Correct signed usage');
 });
 it('round trips seconds without corrupting the offset timestamp', () => {
  const original = '2026-09-07T09:00:15+05:30'; expect(new Date(timestampWithOffset(localTimestampValue(original))).getTime()).toBe(new Date(original).getTime());
 });
});
