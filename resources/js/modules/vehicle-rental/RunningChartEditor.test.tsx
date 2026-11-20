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

it('keeps usage date inputs unconstrained by browser min/max and rejects client-side invalid usage period', async () => {
    render(<RunningChartEditor use={use} onSaved={() => undefined} onCancel={() => undefined} />);

    const startInput = screen.getByLabelText('Usage start');
    const endInput = screen.getByLabelText('Usage end');

    expect(startInput).not.toHaveAttribute('min');
    expect(startInput).not.toHaveAttribute('max');
    expect(endInput).not.toHaveAttribute('min');
    expect(endInput).not.toHaveAttribute('max');

    // End cannot precede start
    fireEvent.change(startInput, { target: { value: '2026-09-07T12:00' } });
    fireEvent.change(endInput, { target: { value: '2026-09-07T11:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    const matchingErrors = await screen.findAllByText('Usage end must follow usage start.');
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

it('allows saving draft usage ending in the future for ongoing custody and submits to API', async () => {
    render(<RunningChartEditor use={use} onSaved={vi.fn()} onCancel={vi.fn()} />);

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T10:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2099-01-01T12:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    await waitFor(() => {
        expect(createChart).toHaveBeenCalledWith(
            use,
            expect.objectContaining({
                starts_at: '2026-09-07T10:00:00+05:30',
                ends_at: '2099-01-01T12:00:00+05:30',
            }),
            undefined,
        );
    });
});

it('prevents submitting usage end after custody return for returned vehicles', async () => {
    const returnedUse: VehicleUse = {
        ...use,
        status: VehicleUseStatus.Returned,
        returned_at: '2026-09-07T18:00:00Z',
    };
    render(<RunningChartEditor use={returnedUse} onSaved={vi.fn()} onCancel={vi.fn()} />);

    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T10:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-08T02:00' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Running Chart' }));

    await waitFor(() => {
        expect(screen.getAllByText('Usage must fall within actual customer custody.').length).toBeGreaterThan(0);
    });
    expect(createChart).not.toHaveBeenCalled();
});

it('automatically calculates overtime based on agreed working hours baseline and allows manual adjustment', async () => {
    const withDriverUse: VehicleUse = {
        ...use,
        customer_agreement: {
            id: 1,
            reference: 'AGR-WD',
            party_name: 'Acme Corp',
            version: 1,
            driver_mode: 'with_driver',
            working_hours: '8.000000',
        },
    };

    render(<RunningChartEditor use={withDriverUse} onSaved={vi.fn()} onCancel={vi.fn()} />);

    expect(screen.getByText('Working Hours & Overtime Baseline')).toBeInTheDocument();
    const baselineInput = screen.getByTitle('Agreed standard daily working hours baseline') as HTMLInputElement;
    expect(baselineInput.value).toBe('8');

    // Enter a 10 hour trip (08:00 to 18:00)
    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T08:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T18:00' } });

    // 10 hours trip with 8 hour baseline -> 2 hours = 120 minutes OT
    await waitFor(() => {
        expect(screen.getByText('120 mins (2 hrs)')).toBeInTheDocument();
        expect(screen.getByText('10 hrs')).toBeInTheDocument();
    });

    // Check normal_ot_minutes input inside details
    fireEvent.click(screen.getByText('Additional usage observations'));
    const normalOtInput = screen.getByLabelText('Normal OT (minutes)') as HTMLInputElement;
    expect(normalOtInput.value).toBe('120');
    expect(screen.getByText('Equivalent: 2 hrs (2.00 hrs)')).toBeInTheDocument();

    // Manually adjust the OT to 150 minutes
    fireEvent.change(normalOtInput, { target: { value: '150' } });
    expect(normalOtInput.value).toBe('150');
    expect(screen.getByText('Equivalent: 2 hrs 30 mins (2.50 hrs)')).toBeInTheDocument();

    await waitFor(() => {
        expect(screen.getByText('Reset to calculated (120m)')).toBeInTheDocument();
    });

    // Click reset to calculated
    fireEvent.click(screen.getByText('Reset to calculated (120m)'));
    expect(normalOtInput.value).toBe('120');

    // Change baseline from 8 to 9 hours -> calculated OT becomes 60 mins
    fireEvent.change(baselineInput, { target: { value: '9' } });
    await waitFor(() => {
        expect(screen.getByText('60 mins (1 hr)')).toBeInTheDocument();
        expect(normalOtInput.value).toBe('60');
    });
});

it('calculates total physical distance from odometer readings and allows setting commercial distance', async () => {
    render(<RunningChartEditor use={use} onSaved={vi.fn()} onCancel={vi.fn()} />);

    fireEvent.change(screen.getByLabelText('Start odometer'), { target: { value: '1000' } });
    fireEvent.change(screen.getByLabelText('End odometer'), { target: { value: '1075.5' } });

    expect(screen.getByText(/Total physical distance:/)).toBeInTheDocument();
    expect(screen.getByText('75.50 km')).toBeInTheDocument();

    const setCommBtn = screen.getByText('Set commercial distance to 75.50 km');
    expect(setCommBtn).toBeInTheDocument();

    fireEvent.click(screen.getByText('Additional usage observations'));
    const commercialInput = screen.getByLabelText('Commercial distance (km)') as HTMLInputElement;
    expect(commercialInput.value).toBe('');

    fireEvent.click(setCommBtn);
    expect(commercialInput.value).toBe('75.50');
});

it('recalculates overtime when editing an existing draft chart that had calculated OT', async () => {
    const withDriverUse: VehicleUse = {
        ...use,
        customer_agreement: {
            id: 1,
            reference: 'AGR-WD',
            party_name: 'Acme Corp',
            version: 1,
            driver_mode: 'with_driver',
            working_hours: '8.000000',
        },
    };

    const existingChart = {
        id: 20,
        row_version: 1,
        reference: 'RC-2026-00020',
        status: RunningChartStatus.Draft,
        starts_at: '2026-09-07T08:00:00+05:30',
        ends_at: '2026-09-07T18:00:00+05:30',
        starts_at_input: '2026-09-07T08:00:00',
        ends_at_input: '2026-09-07T18:00:00',
        normal_ot_minutes: 120, // 10 hours - 8 baseline = 120 mins calculated
        double_ot_minutes: null,
        triple_ot_minutes: null,
        night_outs: null,
        ac_mode: null,
        notes: null,
        driver_observation: null,
        total_km: null,
        corrects_chart: null,
    } as unknown as RunningChart;

    render(<RunningChartEditor use={withDriverUse} chart={existingChart} onSaved={vi.fn()} onCancel={vi.fn()} />);

    const normalOtInput = screen.getByLabelText('Normal OT (minutes)') as HTMLInputElement;
    expect(normalOtInput.value).toBe('120');

    // Change usage end from 18:00 (10 hrs) to 17:00 (9 hrs)
    // Overtime should automatically update from 120 to 60 mins because it wasn't manually overridden
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T17:00' } });

    await waitFor(() => {
        expect(screen.getByText('60 mins (1 hr)')).toBeInTheDocument();
        expect(normalOtInput.value).toBe('60');
    });
});

it('displays clear message when overtime is redirected to double OT without blank text artifact', async () => {
    const withDriverUse: VehicleUse = {
        ...use,
        customer_agreement: {
            id: 1,
            reference: 'AGR-WD',
            party_name: 'Acme Corp',
            version: 1,
            driver_mode: 'with_driver',
            working_hours: '8.000000',
        },
    };

    render(<RunningChartEditor use={withDriverUse} onSaved={vi.fn()} onCancel={vi.fn()} />);

    // 10 hour trip -> 120 mins OT
    fireEvent.change(screen.getByLabelText('Usage start'), { target: { value: '2026-09-07T08:00' } });
    fireEvent.change(screen.getByLabelText('Usage end'), { target: { value: '2026-09-07T18:00' } });

    await waitFor(() => {
        expect(screen.getByText('Apply to Double OT')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByText('Apply to Double OT'));

    const normalOtInput = screen.getByLabelText('Normal OT (minutes)') as HTMLInputElement;
    expect(normalOtInput.value).toBe('');

    const doubleOtInput = screen.getByLabelText('Double OT (minutes)') as HTMLInputElement;
    expect(doubleOtInput.value).toBe('120');

    // Check redirection message does not contain empty '( mins vs ...)'
    expect(screen.getByText(/Applied 120 mins to Double OT/)).toBeInTheDocument();
    expect(screen.queryByText(/Manually adjusted \( mins/)).not.toBeInTheDocument();
});
