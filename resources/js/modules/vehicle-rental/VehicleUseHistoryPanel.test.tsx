import { render, screen } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { VehicleUseHistoryPanel } from './VehicleUseHistoryPanel';
import { configureBusinessTimeZone, formatBusinessDateTime } from '@/shared/utils/businessDate';
import { vehicleUseHistory } from './vehicleUseApi';
import { VehicleUseStatus } from './vehicleUse';

vi.mock('./vehicleUseApi', () => ({ vehicleUseHistory: vi.fn() }));

beforeEach(() => {
    configureBusinessTimeZone('Asia/Colombo');
    vi.clearAllMocks();
    vi.mocked(vehicleUseHistory).mockResolvedValue({
        data: [{
            version: 3,
            action: 'handover',
            reason: 'Customer collected vehicle',
            recorded_at: '2026-10-01T09:00:00+05:30',
            actor: { name: 'Rental Admin' },
            vehicle_label: 'CAR-1234',
            status: VehicleUseStatus.InCustody,
            starts_at: '2026-10-01T09:00:00+05:30',
            ends_at: null,
            handed_over_at: '2026-10-01T09:00:00+05:30',
            returned_at: null,
            handover_odometer: '12500.000000',
            return_odometer: null,
        }],
    });
});
afterEach(() => configureBusinessTimeZone(null));

it('shows business status labels and formatted immutable custody evidence', async () => {
    render(<VehicleUseHistoryPanel id={3} />);
    expect(await screen.findByText('With customer')).toBeInTheDocument();
    expect(screen.getByText(/CAR-1234/)).toHaveTextContent('Open-ended');
    expect(screen.getByText(/CAR-1234/)).toHaveTextContent(formatBusinessDateTime('2026-10-01T09:00:00+05:30'));
    expect(screen.queryByText('2026-10-01T09:00:00+05:30')).not.toBeInTheDocument();
    expect(screen.getByText(/Odometer:/)).toHaveTextContent('12,500');
    expect(screen.queryByText(/12500\.000000/)).not.toBeInTheDocument();
    expect(screen.getByText(/Customer collected vehicle/)).toBeInTheDocument();
});
