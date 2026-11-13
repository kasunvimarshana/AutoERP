import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ReassignDriverModal } from './ReassignDriverModal';
import { AirConditioningMode, DriverIdentitySource, RunningChartStatus, type RunningChart } from './runningCharts';
import * as agreementApi from './agreementApi';

vi.mock('./agreementApi', () => ({
    reassignRunningChartDriver: vi.fn(),
}));

vi.mock('@/shared/api/lookupRequest', () => ({
    requestLookup: vi.fn().mockResolvedValue({
        data: [{ id: 55, display_name: 'John Perera', employee_number: 'EMP-055' }],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
    }),
}));

const mockChart: RunningChart = {
    id: 105,
    reference: 'RC-202609-0105',
    vehicle_use_id: 12,
    row_version: 2,
    status: RunningChartStatus.Draft,
    starts_at: '2026-09-10T08:00:00+00:00',
    ends_at: '2026-09-10T17:00:00+00:00',
    start_odometer: '10000.000',
    end_odometer: '10150.000',
    total_km: '150.000',
    air_conditioning: AirConditioningMode.Dual,
    normal_ot_minutes: 60,
    double_ot_minutes: 0,
    night_outs: 0,
    tolls_amount: '250.000000',
    driver_identity_source: DriverIdentitySource.Employee,
    driver: {
        source: DriverIdentitySource.Employee,
        employee_id: 42,
        name: 'Kamal Silva',
        reference: 'EMP-042',
    },
    corrects_chart: null,
    corrected_by_chart: null,
    charges: [],
};

describe('ReassignDriverModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('displays current driver information and reassigns driver on confirmation', async () => {
        const viReassign = vi.mocked(agreementApi.reassignRunningChartDriver).mockResolvedValue({
            id: 1,
            reassignment_type: 'driver_reassignment',
            running_chart_id: 105,
            previous_driver_employee_id: 42,
            new_driver_employee_id: 55,
            financial_status: 'none',
            financial_delta_amount: '0.000000',
            reason: 'Driver shift handover',
            occurred_at: '2026-09-10T12:00:00+00:00',
        });

        const onClose = vi.fn();
        const onSaved = vi.fn();

        render(
            <ReassignDriverModal
                open={true}
                chart={mockChart}
                onClose={onClose}
                onSaved={onSaved}
            />,
        );

        expect(screen.getByText(/Reassign Driver for Chart RC-202609-0105/i)).toBeInTheDocument();
        expect(screen.getByText(/Kamal Silva \(EMP-042\)/i)).toBeInTheDocument();

        // Fill reason
        const reasonInput = screen.getByLabelText(/Reassignment Reason \/ Justification/i);
        fireEvent.change(reasonInput, { target: { value: 'Driver shift handover' } });

        // Open lookup select and pick driver
        fireEvent.click(screen.getByRole('combobox', { name: /New Driver Employee/i }));
        fireEvent.click(await screen.findByRole('option', { name: /John Perera/i }));

        // Submit form
        const submitBtn = screen.getByRole('button', { name: /Confirm Driver Reassignment/i });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(viReassign).toHaveBeenCalledWith(105, 55, 'Driver shift handover', 2);
            expect(onSaved).toHaveBeenCalled();
            expect(onClose).toHaveBeenCalled();
        });
    });
});
