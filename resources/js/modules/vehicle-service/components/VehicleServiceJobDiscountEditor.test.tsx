import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { VehicleServiceJobDiscountEditor } from './VehicleServiceJobDiscountEditor';

const apiMocks = vi.hoisted(() => ({
    removeVehicleServiceJobDiscount: vi.fn(),
    setVehicleServiceJobDiscount: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

describe('VehicleServiceJobDiscountEditor', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('saves a percentage discount against the current job version', async () => {
        const user = userEvent.setup();
        const job = makeJob();
        const updated = { ...job, row_version: 8, job_discount_amount: '20.000000' };
        const onChanged = vi.fn();
        apiMocks.setVehicleServiceJobDiscount.mockResolvedValue(updated);

        render(<VehicleServiceJobDiscountEditor job={job} onChanged={onChanged} />);

        await user.click(screen.getByRole('button', { name: 'Add job discount' }));
        await user.selectOptions(screen.getByLabelText('Discount type'), 'percentage');
        await user.clear(screen.getByLabelText('Rate (%)'));
        await user.type(screen.getByLabelText('Rate (%)'), '10');
        await user.type(screen.getByLabelText('Reason'), 'Loyal customer');
        await user.click(screen.getByRole('button', { name: 'Add discount' }));

        await waitFor(() => expect(apiMocks.setVehicleServiceJobDiscount).toHaveBeenCalledWith(9, {
            expected_version: 7,
            calculation_type: 'percentage',
            rate: '10',
            fixed_amount: '0.000000',
            reason: 'Loyal customer',
        }));
        expect(onChanged).toHaveBeenCalledWith(updated);
    });

    it('removes the active discount with a reason and version check', async () => {
        const user = userEvent.setup();
        const job = makeJob({
            job_discount_amount: '20.000000',
            grand_total: '180.000000',
            job_discount: {
                id: 4,
                revision: 1,
                action: 'set',
                calculation_type: 'fixed',
                rate: '0.000000',
                fixed_amount: '20.000000',
                calculation_base: '200.000000',
                calculated_amount: '20.000000',
                reason: 'Price match',
                changed_at: '2026-08-14T09:00:00Z',
                changed_by: null,
            },
        });
        const updated = makeJob({ row_version: 8 });
        const onChanged = vi.fn();
        apiMocks.removeVehicleServiceJobDiscount.mockResolvedValue(updated);

        render(<VehicleServiceJobDiscountEditor job={job} onChanged={onChanged} />);

        await user.click(screen.getByRole('button', { name: 'Edit job discount' }));
        await user.type(screen.getByLabelText('Reason'), 'Approved correction');
        await user.click(screen.getByRole('button', { name: 'Remove discount' }));

        await waitFor(() => expect(apiMocks.removeVehicleServiceJobDiscount)
            .toHaveBeenCalledWith(9, 7, 'Approved correction'));
        expect(onChanged).toHaveBeenCalledWith(updated);
    });
});

function makeJob(overrides: Partial<VehicleServiceJob> = {}): VehicleServiceJob {
    return {
        id: 9,
        row_version: 7,
        job_number: 'JOB-9',
        job_date: '2026-08-14',
        type: 'full_service',
        status: 'draft',
        subtotal: '200.000000',
        line_discount_total: '0.000000',
        job_discount_base: '200.000000',
        job_discount_amount: '0.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '200.000000',
        supervisor_commission_type: 'none',
        supervisor_commission_value: '0.000000',
        supervisor_commission_amount: '0.000000',
        ...overrides,
    } as VehicleServiceJob;
}
