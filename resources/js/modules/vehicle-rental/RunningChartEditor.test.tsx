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
    const driverIdentity = screen.getByRole('combobox', { name: 'Driver identity' });
    expect(driverIdentity).toHaveValue('');
    expect(driverIdentity.querySelectorAll('option[value=""]')).toHaveLength(1);
    expect(driverIdentity.querySelector('option[value=""]')).toHaveTextContent('Not recorded');
});

it('keeps save disabled until required chart identity and period are complete', () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);
    const save = screen.getByRole('button', { name: 'Save draft' });
    expect(save).toBeDisabled();

    fireEvent.change(screen.getByLabelText('Chart reference'), { target: { value: 'CHART-NEW' } });
    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T09:00' } });
    expect(screen.getByLabelText('Usage end')).toHaveAttribute('min', '2026-09-07T09:00');
    expect(save).toBeDisabled();

    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T17:00' } });
    expect(save).toBeEnabled();
});

it('keeps save disabled until selected driver identity details are complete', () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);
    fireEvent.change(screen.getByLabelText('Chart reference'), { target: { value: 'CHART-NEW' } });
    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T09:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T17:00' } });
    fireEvent.click(screen.getByText('Additional usage observations'));
    fireEvent.change(screen.getByLabelText('Driver identity'), { target: { value: 'external' } });

    expect(screen.getByRole('button', { name: 'Save draft' })).toBeDisabled();
    fireEvent.change(screen.getByLabelText('External driver name'), { target: { value: 'External Driver' } });
    fireEvent.change(screen.getByLabelText('External driver reference'), { target: { value: 'DRV-X' } });
    expect(screen.getByRole('button', { name: 'Save draft' })).toBeEnabled();
});
