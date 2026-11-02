import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { RentalAssignDriverModal } from './RentalAssignDriverModal';

const apiMocks = vi.hoisted(() => ({
    assignRentalDriver: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => apiMocks);

vi.mock('@/modules/hr/components/DriverLookupSelect', () => ({
    DriverLookupSelect: ({
        label,
        onChange,
    }: {
        label: string;
        onChange: (driver: { id: number; name: string } | null) => void;
    }) => (
        <div>
            <label htmlFor="driver-input">{label}</label>
            <input id="driver-input" readOnly />
            <button
                type="button"
                data-testid="pick-driver"
                onClick={() => onChange({ id: 42, name: 'Sunil Perera' })}
            >
                Pick Driver
            </button>
        </div>
    ),
}));

describe('RentalAssignDriverModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.assignRentalDriver.mockResolvedValue({ id: 101 });
    });

    it('renders and submits primary driver assignment to an allocation', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        const onSuccess = vi.fn();

        render(
            <RentalAssignDriverModal
                open={true}
                allocationId={55}
                allocationNumber="AL-00055"
                allocatedFrom="2026-07-01T08:00:00.000Z"
                allocatedTo="2026-07-31T18:00:00.000Z"
                initialRole="primary"
                onClose={onClose}
                onSuccess={onSuccess}
            />,
        );

        expect(screen.getByText('Assign Driver to AL-00055')).toBeInTheDocument();
        expect(screen.getByLabelText('Driver')).toBeInTheDocument();
        expect(screen.getByLabelText('Assignment role')).toHaveValue('primary');

        await user.click(screen.getByTestId('pick-driver'));

        const submitBtn = screen.getByRole('button', { name: 'Assign driver' });
        expect(submitBtn).not.toBeDisabled();

        await user.click(submitBtn);

        await waitFor(() => {
            expect(apiMocks.assignRentalDriver).toHaveBeenCalledWith(55, expect.objectContaining({
                employee_id: 42,
                assignment_role: 'primary',
                is_primary: true,
            }));
        });

        expect(onSuccess).toHaveBeenCalledTimes(1);
        expect(onClose).toHaveBeenCalledTimes(1);
    });

    it('submits relief driver assignment when role is changed to relief', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        const onSuccess = vi.fn();

        render(
            <RentalAssignDriverModal
                open={true}
                allocationId={55}
                allocationNumber="AL-00055"
                allocatedFrom="2026-07-01T08:00:00.000Z"
                allocatedTo="2026-07-31T18:00:00.000Z"
                initialRole="relief"
                onClose={onClose}
                onSuccess={onSuccess}
            />,
        );

        expect(screen.getByLabelText('Assignment role')).toHaveValue('relief');

        await user.click(screen.getByTestId('pick-driver'));
        await user.click(screen.getByRole('button', { name: 'Assign driver' }));

        await waitFor(() => {
            expect(apiMocks.assignRentalDriver).toHaveBeenCalledWith(55, expect.objectContaining({
                employee_id: 42,
                assignment_role: 'relief',
                is_primary: false,
            }));
        });
    });
});
