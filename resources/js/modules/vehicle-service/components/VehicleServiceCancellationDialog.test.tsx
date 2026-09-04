import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import type { VehicleServiceCancellationPreview, VehicleServiceJob } from '../vehicleServiceTypes';
import { VehicleServiceCancellationDialog } from './VehicleServiceCancellationDialog';

const api = vi.hoisted(() => ({ getVehicleServiceCancellationPreview: vi.fn(), cancelVehicleServiceJob: vi.fn() }));
vi.mock('../vehicleServiceApi', () => api);
const job = { id: 4, job_number: 'VSJ-000004', row_version: 1 } as VehicleServiceJob;
const preview: VehicleServiceCancellationPreview = {
    row_version: 5, can_cancel: true, blockers: [], inventory_value: '100.000000', commission_amount: '80.000000',
    stock_returns: [{ description: 'Oil filter', quantity: '2.000000', uom: 'Pieces', warehouse: 'Main store', location: 'Shelf A' }],
};

describe('VehicleServiceCancellationDialog', () => {
    beforeEach(() => {
        vi.resetAllMocks();
        api.getVehicleServiceCancellationPreview.mockResolvedValue(preview);
    });

    it('shows impacts and submits a required reason using the preview version', async () => {
        const user = userEvent.setup();
        const onCancelled = vi.fn().mockResolvedValue(undefined);
        const cancelled = { ...job, status: 'cancelled', row_version: 6 };
        api.cancelVehicleServiceJob.mockResolvedValue(cancelled);
        render(<VehicleServiceCancellationDialog job={job} onClose={vi.fn()} onCancelled={onCancelled} />);
        await screen.findByLabelText('Cancellation reason');
        expect(screen.getByText(/Oil filter/)).toHaveTextContent('Main store / Shelf A');
        expect(screen.getByText(/physically returned/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Confirm cancellation' })).toBeDisabled();
        await user.type(screen.getByLabelText('Cancellation reason'), '  Customer cancelled  ');
        await user.click(screen.getByRole('button', { name: 'Confirm cancellation' }));
        await waitFor(() => expect(api.cancelVehicleServiceJob).toHaveBeenCalledWith(4, 5, 'Customer cancelled'));
        expect(onCancelled).toHaveBeenCalledWith(cancelled);
    });

    it('blocks submission when a linked invoice protects the job', async () => {
        api.getVehicleServiceCancellationPreview.mockResolvedValue({ ...preview, can_cancel: false, blockers: ['Reverse the linked invoice first.'] });
        render(<VehicleServiceCancellationDialog job={job} onClose={vi.fn()} onCancelled={vi.fn()} />);
        await screen.findByText('Reverse the linked invoice first.');
        expect(screen.getByRole('button', { name: 'Confirm cancellation' })).toBeDisabled();
        expect(screen.queryByLabelText('Cancellation reason')).not.toBeInTheDocument();
        expect(api.cancelVehicleServiceJob).not.toHaveBeenCalled();
    });

    it('refreshes the preview after a conflict and does not retry automatically', async () => {
        const user = userEvent.setup();
        api.cancelVehicleServiceJob.mockRejectedValue(new ApiError('Reload the changed job.', 422, 'VALIDATION_ERROR', 'validation'));
        render(<VehicleServiceCancellationDialog job={job} onClose={vi.fn()} onCancelled={vi.fn()} />);
        await user.type(await screen.findByLabelText('Cancellation reason'), 'Customer cancelled');
        await user.click(screen.getByRole('button', { name: 'Confirm cancellation' }));
        await screen.findByText('Reload the changed job.');
        await waitFor(() => expect(api.getVehicleServiceCancellationPreview).toHaveBeenCalledTimes(2));
        expect(api.cancelVehicleServiceJob).toHaveBeenCalledTimes(1);
    });
});
