import { fireEvent, render, screen } from '@testing-library/react';
import { expect, it } from 'vitest';
import { RunningChartEditor } from './RunningChartEditor';
import { RunningChartStatus, type RunningChart } from './runningCharts';
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

it('allows leaving chart reference blank to automatically generate unique reference on creation', () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);
    expect(screen.getByText('Leave blank to automatically generate a unique chart reference')).toBeInTheDocument();
    const save = screen.getByRole('button', { name: 'Save draft' });
    expect(save).toBeDisabled();

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T09:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T17:00' } });
    expect(save).toBeEnabled();
});

it('requires chart reference when editing an existing draft chart', () => {
    const existingChart = {
        id: 15,
        row_version: 1,
        reference: 'RC-2026-00001',
        status: RunningChartStatus.Draft,
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: '2026-09-07T17:00:00+05:30',
        start_odometer: null,
        end_odometer: null,
        garage_km: null,
        commercial_km: null,
        normal_ot_minutes: null,
        double_ot_minutes: null,
        triple_ot_minutes: null,
        night_outs: null,
        ac_mode: null,
        notes: null,
        driver_observation: null,
        total_km: null,
        corrects_chart: null,
    } as RunningChart;
    render(<RunningChartEditor use={use} chart={existingChart} onSaved={() => undefined} onCancel={() => undefined} />);
    expect(screen.queryByText('Leave blank to automatically generate a unique chart reference')).not.toBeInTheDocument();
    const refInput = screen.getByLabelText('Chart reference');
    expect(refInput).toHaveAttribute('required');

    const save = screen.getByRole('button', { name: 'Save draft' });
    expect(save).toBeEnabled();

    fireEvent.change(refInput, { target: { value: '' } });
    expect(save).toBeDisabled();
});
