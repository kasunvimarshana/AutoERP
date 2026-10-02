import { render, screen } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
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
