import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { configureBusinessTimeZone, formatBusinessDateTime } from '@/shared/utils/businessDate';
import { RunningChartsPanel } from './RunningChartsPanel';
import { listCharts, transitionChart } from './runningChartApi';
import { RunningChartAction, RunningChartStatus, CHART_PERMISSION, type RunningChart } from './runningCharts';
import { VehicleUseStatus, localTimestampValue, timestampWithOffset, type VehicleUse } from './vehicleUse';
const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./runningChartApi', () => ({ listCharts: vi.fn(), transitionChart: vi.fn(), chartHistory: vi.fn(), createChart: vi.fn(), updateChart: vi.fn() }));
const use = { id: 3, row_version: 2, status: VehicleUseStatus.InCustody, vehicle: { id: 9, label: 'CAR-1234' } } as VehicleUse;
const chart = { id: 5, row_version: 2, reference: 'CHART-A', status: RunningChartStatus.Finalized, starts_at: '2026-09-07T09:00:15+05:30', ends_at: '2026-09-07T17:00:30+05:30', total_km: null, corrects_chart: null } as RunningChart;
beforeEach(() => { configureBusinessTimeZone('Asia/Colombo'); vi.clearAllMocks(); session.permissions = [CHART_PERMISSION.view]; vi.mocked(listCharts).mockResolvedValue({ data: [chart] }); });
afterEach(() => configureBusinessTimeZone(null));
describe('Running Chart review', () => {
 it('preserves unknown distance and hides unauthorized actions', async () => {
  render(<RunningChartsPanel use={use} />); expect(await screen.findByText('CHART-A')).toBeInTheDocument(); expect(screen.getByText('Finalized')).toBeInTheDocument(); expect(screen.getByText('Total distance: Not recorded')).toBeInTheDocument(); expect(screen.getByText(`${formatBusinessDateTime(chart.starts_at)} — ${formatBusinessDateTime(chart.ends_at)}`)).toBeInTheDocument(); expect(screen.queryByRole('button', { name: 'Reverse usage' })).not.toBeInTheDocument(); expect(screen.queryByRole('button', { name: 'Record usage' })).not.toBeInTheDocument();
 });
 it('requires a reason and preserves stale revision errors', async () => {
  session.permissions.push(CHART_PERMISSION.reverse); vi.mocked(transitionChart).mockRejectedValue(new ApiError('This record changed. Reload before continuing.', 409));
  render(<RunningChartsPanel use={use} />); fireEvent.click(await screen.findByRole('button', { name: 'Reverse usage' })); expect(screen.getByRole('button', { name: 'Confirm reversal' })).toBeDisabled(); fireEvent.change(screen.getByLabelText('Reversal reason'), { target: { value: 'Correct signed usage' } }); fireEvent.click(screen.getByRole('button', { name: 'Confirm reversal' })); expect(await screen.findByText('This record changed. Reload before continuing.')).toBeInTheDocument(); expect(transitionChart).toHaveBeenCalledWith(chart, RunningChartAction.Reverse, 'Correct signed usage');
 });
 it('round trips seconds without corrupting the offset timestamp', () => {
  const original = '2026-09-07T09:00:15+05:30'; expect(new Date(timestampWithOffset(localTimestampValue(original))).getTime()).toBe(new Date(original).getTime());
 });
 it('displays corrected by chart badge and hides create correction button when chart was already corrected', async () => {
  session.permissions.push(CHART_PERMISSION.manage);
  const reversedChart = {
   ...chart,
   id: 6,
   status: RunningChartStatus.Reversed,
   corrected_by_chart: { id: 7, reference: 'CHART-CORRECTION' },
  };
  vi.mocked(listCharts).mockResolvedValue({ data: [reversedChart] });
  render(<RunningChartsPanel use={use} />);
  expect(await screen.findByText('Corrected by chart CHART-CORRECTION')).toBeInTheDocument();
  expect(screen.queryByRole('button', { name: 'Create correction' })).not.toBeInTheDocument();
 });
 it('removes stale charts when a reload fails', async () => {
  render(<RunningChartsPanel use={use} />);
  expect(await screen.findByText('CHART-A')).toBeInTheDocument();
  vi.mocked(listCharts).mockRejectedValue(new ApiError('Charts could not be loaded.', 500));
  fireEvent.click(screen.getByRole('button', { name: 'Reload charts' }));
  expect(await screen.findByText('Charts could not be loaded.')).toBeInTheDocument();
  expect(screen.queryByText('CHART-A')).not.toBeInTheDocument();
 });
 it('disables finalization and badges upcoming and ongoing draft charts', async () => {
  session.permissions.push(CHART_PERMISSION.finalize);
  const pastDraft = {
   ...chart,
   id: 10,
   reference: 'CHART-PAST',
   status: RunningChartStatus.Draft,
   starts_at: '2026-09-07T09:00:00+05:30',
   ends_at: '2026-09-07T12:00:00+05:30',
  };
  const upcomingDraft = {
   ...chart,
   id: 11,
   reference: 'CHART-UPCOMING',
   status: RunningChartStatus.Draft,
   starts_at: '2099-01-01T09:00:00+05:30',
   ends_at: '2099-01-01T12:00:00+05:30',
  };
  const ongoingDraft = {
   ...chart,
   id: 12,
   reference: 'CHART-ONGOING',
   status: RunningChartStatus.Draft,
   starts_at: '2026-09-07T09:00:00+05:30',
   ends_at: '2099-01-01T12:00:00+05:30',
  };
  vi.mocked(listCharts).mockResolvedValue({ data: [pastDraft, upcomingDraft, ongoingDraft] });
  render(<RunningChartsPanel use={use} />);
  expect(await screen.findByText('CHART-PAST')).toBeInTheDocument();
  expect(screen.getByText('Ready to finalize')).toBeInTheDocument();
  expect(screen.getByText('Upcoming trip')).toBeInTheDocument();
  expect(screen.getByText('Trip in progress')).toBeInTheDocument();

  const finalizeButtons = screen.getAllByRole('button', { name: 'Finalize usage' });
  expect(finalizeButtons).toHaveLength(3);
  expect(finalizeButtons[0]).toBeEnabled();
  expect(finalizeButtons[1]).toBeDisabled();
  expect(finalizeButtons[2]).toBeDisabled();
 });
 it('badges custody overrun and disables finalization when draft ends after return', async () => {
  session.permissions.push(CHART_PERMISSION.finalize);
  const returnedUse = {
   ...use,
   status: VehicleUseStatus.Returned,
   handed_over_at: '2026-09-07T09:00:00+05:30',
   returned_at: '2026-09-07T15:00:00+05:30',
  } as VehicleUse;
  const overrunDraft = {
   ...chart,
   id: 20,
   reference: 'CHART-OVERRUN',
   status: RunningChartStatus.Draft,
   starts_at: '2026-09-07T09:00:00+05:30',
   ends_at: '2026-09-07T16:00:00+05:30',
  };
  vi.mocked(listCharts).mockResolvedValue({ data: [overrunDraft] });
  render(<RunningChartsPanel use={returnedUse} />);
  expect(await screen.findByText('CHART-OVERRUN')).toBeInTheDocument();
  expect(screen.getByText('Exceeds return custody')).toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'Finalize usage' })).toBeDisabled();
 });
});
