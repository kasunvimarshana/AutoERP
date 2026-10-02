import { fireEvent, render, screen } from '@testing-library/react';
import { expect, it } from 'vitest';
import { RunningChartEditor } from './RunningChartEditor';
import { VehicleUseStatus, type VehicleUse } from './vehicleUse';

const use = {
    id: 12,
    row_version: 2,
    status: VehicleUseStatus.InCustody,
    vehicle: { id: 6, label: 'CAR-1234' },
} as VehicleUse;

it('uses integer controls for OT minutes and night-outs while keeping distance fields decimal', () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);
    fireEvent.click(screen.getByText('Additional usage observations'));

    const normalOt = screen.getByLabelText('Normal OT (minutes)');
    const nightOuts = screen.getByLabelText('Night-outs');
    const odometer = screen.getByLabelText('Start odometer');

    expect(normalOt).toHaveAttribute('type', 'number');
    expect(normalOt).toHaveAttribute('min', '0');
    expect(normalOt).toHaveAttribute('step', '1');
    expect(nightOuts).toHaveAttribute('type', 'number');
    expect(nightOuts).toHaveAttribute('step', '1');
    expect(odometer).toHaveAttribute('inputmode', 'decimal');
    expect(screen.getByRole('combobox', { name: 'Air conditioning' })).toHaveValue('');
});
