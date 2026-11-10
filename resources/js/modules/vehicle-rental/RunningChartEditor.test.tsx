import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, it, vi } from 'vitest';
import { RunningChartEditor } from './RunningChartEditor';
import { RunningChartStatus, type RunningChart } from './runningCharts';
import { VehicleUseStatus, type VehicleUse } from './vehicleUse';
import { createChart } from './runningChartApi';

vi.mock('./runningChartApi', () => ({
    createChart: vi.fn(),
    updateChart: vi.fn(),
}));

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

it('submits null for blank optional fields when creating a chart', async () => {
    vi.mocked(createChart).mockResolvedValueOnce({} as RunningChart);
    const onSaved = vi.fn();
    render(<RunningChartEditor use={use} onSaved={onSaved} onCancel={() => undefined} />);

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T09:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T17:00' } });
    fireEvent.change(screen.getByLabelText('Start odometer'), { target: { value: '   ' } });
    fireEvent.change(screen.getByLabelText('End odometer'), { target: { value: ' ' } });

    fireEvent.click(screen.getByRole('button', { name: 'Save draft' }));

    await waitFor(() => {
        expect(createChart).toHaveBeenCalledWith(
            use,
            expect.objectContaining({
                start_odometer: null,
                end_odometer: null,
                garage_km: null,
                commercial_km: null,
                normal_ot_minutes: null,
                double_ot_minutes: null,
                triple_ot_minutes: null,
                night_outs: null,
                ac_mode: null,
                driver_identity_source: null,
                driver_employee_id: null,
                driver_name_snapshot: null,
                driver_reference_snapshot: null,
                driver_observation: null,
                notes: null,
            }),
            undefined
        );
        expect(onSaved).toHaveBeenCalled();
    });
});

it('restricts usage end input to current time or return time and rejects client-side future usage', async () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);

    const startInput = screen.getByLabelText('Usage start');
    const endInput = screen.getByLabelText('Usage end');

    expect(endInput).toHaveAttribute('max');
    const maxEnd = endInput.getAttribute('max')!;
    expect(maxEnd.length).toBeGreaterThanOrEqual(16);

    // End cannot precede start
    fireEvent.change(startInput, { target: { value: '2026-09-07T12:00' } });
    fireEvent.change(endInput, { target: { value: '2026-09-07T11:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    const matchingErrors = await screen.findAllByText('Record completed usage ending after its start, not in the future.');
    expect(matchingErrors.length).toBeGreaterThanOrEqual(1);
    expect(createChart).not.toHaveBeenCalled();
});

it('sets end time via Use current time button and respects custody return button when present', () => {
    const returnedUse = {
        ...use,
        status: VehicleUseStatus.Returned,
        handed_over_at: '2026-09-07T08:00:38+05:30',
        returned_at: '2026-09-07T18:00:45+05:30',
    } as VehicleUse;

    render(<RunningChartEditor use={returnedUse} onSaved={() => undefined} onCancel={() => undefined} />);

    expect(screen.getByRole('button', { name: 'Use custody start time' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Use custody return time' })).toBeInTheDocument();

    const endInput = screen.getByLabelText('Usage end') as HTMLInputElement;
    fireEvent.click(screen.getByRole('button', { name: 'Use custody return time' }));
    expect(endInput.value).toContain('2026-09-07T18:00:45');

    const startInput = screen.getByLabelText('Usage start') as HTMLInputElement;
    fireEvent.click(screen.getByRole('button', { name: 'Use custody start time' }));
    expect(startInput.value).toContain('2026-09-07T08:00:38');
});

it('submits valid usage period directly to API without redundant client clock barriers', async () => {
    vi.mocked(createChart).mockResolvedValueOnce({} as RunningChart);
    const onSaved = vi.fn();
    render(<RunningChartEditor use={use} onSaved={onSaved} onCancel={() => undefined} />);

    const startInput = screen.getByLabelText('Usage start');
    const endInput = screen.getByLabelText('Usage end');

    fireEvent.change(startInput, { target: { value: '2026-09-07T10:00' } });
    fireEvent.change(endInput, { target: { value: '2026-09-07T12:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    await waitFor(() => {
        expect(createChart).toHaveBeenCalledWith(
            use,
            expect.objectContaining({
                starts_at: expect.stringContaining('2026-09-07T10:00'),
                ends_at: expect.stringContaining('2026-09-07T12:00'),
            }),
            undefined,
        );
    });
});

it('prevents submitting usage start prior to customer custody with immediate feedback', async () => {
    const custodyUse = {
        ...use,
        status: VehicleUseStatus.InCustody,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
    } as VehicleUse;

    render(<RunningChartEditor use={custodyUse} onSaved={vi.fn()} onCancel={vi.fn()} />);

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T07:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T10:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    await waitFor(() => {
        expect(screen.getAllByText('Usage must fall within actual customer custody.').length).toBeGreaterThan(0);
    });
    expect(createChart).not.toHaveBeenCalled();
});

it('prevents submitting usage end in the future with immediate validation feedback', async () => {
    render(<RunningChartEditor use={use} onSaved={vi.fn()} onCancel={vi.fn()} />);

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T10:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2099-01-01T12:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    await waitFor(() => {
        expect(screen.getAllByText('Record completed usage ending after its start, not in the future.').length).toBeGreaterThan(0);
    });
    expect(createChart).not.toHaveBeenCalled();
});
