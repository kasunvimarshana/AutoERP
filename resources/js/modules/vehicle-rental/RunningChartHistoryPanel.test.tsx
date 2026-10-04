import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { RunningChartHistoryPanel } from './RunningChartHistoryPanel';
import { chartHistory } from './runningChartApi';
import { AirConditioningMode, DriverIdentitySource } from './runningCharts';

vi.mock('./runningChartApi', () => ({ chartHistory: vi.fn() }));

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(chartHistory).mockResolvedValue({
        data: [{
            version: 2,
            action: 'reverse',
            reason: 'Correct signed sheet',
            recorded_at: '2026-10-01T18:00:00+05:30',
            actor: { name: 'Rental Admin' },
            facts: {
                reference: 'CHART-A',
                starts_at: '2026-10-01T09:00:00+05:30',
                ends_at: '2026-10-01T17:00:00+05:30',
                start_odometer: '100.000000',
                end_odometer: '150.000000',
                garage_km: '0.000000',
                commercial_km: '50.000000',
                normal_ot_minutes: 90,
                double_ot_minutes: 0,
                triple_ot_minutes: null,
                night_outs: 1,
                ac_mode: AirConditioningMode.Front,
                driver_identity_source: DriverIdentitySource.External,
                driver_employee_id: null,
                driver_name_snapshot: 'External Driver',
                driver_reference_snapshot: 'DRV-X',
                driver_observation: 'Signed',
                notes: 'Verified',
            },
        }],
    });
});

it('shows complete formatted chart revision evidence without raw storage precision', async () => {
    render(<RunningChartHistoryPanel id={9} />);
    expect(await screen.findByText(/Revision 2 · Reverse/)).toBeInTheDocument();
    expect(screen.getByText('Front AC')).toBeInTheDocument();
    expect(screen.getByText(/External driver · External Driver · DRV-X/)).toBeInTheDocument();
    expect(screen.getByText('Garage distance (km)').parentElement).toHaveTextContent('0');
    expect(screen.getByText('Commercial distance (km)').parentElement).toHaveTextContent('50');
    expect(screen.getByText('Normal OT (minutes)').parentElement).toHaveTextContent('90');
    expect(screen.queryByText(/\.000000/)).not.toBeInTheDocument();
    expect(screen.getByText('Notes: Verified')).toBeInTheDocument();
});

it('shows immutable employee driver snapshots when the chart used an employee driver', async () => {
    vi.mocked(chartHistory).mockResolvedValue({
        data: [{
            version: 3,
            action: 'finalize',
            reason: null,
            recorded_at: '2026-10-01T18:00:00+05:30',
            actor: { name: 'Rental Admin' },
            facts: {
                reference: 'CHART-B',
                starts_at: '2026-10-01T09:00:00+05:30',
                ends_at: '2026-10-01T17:00:00+05:30',
                start_odometer: null,
                end_odometer: null,
                garage_km: null,
                commercial_km: null,
                normal_ot_minutes: null,
                double_ot_minutes: null,
                triple_ot_minutes: null,
                night_outs: null,
                ac_mode: null,
                driver_identity_source: DriverIdentitySource.Employee,
                driver_employee_id: 27,
                driver_name_snapshot: 'Rental Driver One',
                driver_reference_snapshot: 'DRV-001',
                driver_observation: null,
                notes: null,
            },
        }],
    });
    render(<RunningChartHistoryPanel id={10} />);
    expect(await screen.findByText(/Employee driver · Rental Driver One · DRV-001/)).toBeInTheDocument();
});

it('lets the operator retry a failed Running Chart history request', async () => {
    vi.mocked(chartHistory).mockRejectedValueOnce(new ApiError('Running Chart history unavailable.', 500));
    render(<RunningChartHistoryPanel id={9} />);
    expect(await screen.findByText('Running Chart history unavailable.')).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Retry Running Chart history' }));
    expect(await screen.findByText(/Revision 2 · Reverse/)).toBeInTheDocument();
});
