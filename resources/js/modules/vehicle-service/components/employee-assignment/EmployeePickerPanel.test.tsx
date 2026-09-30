import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { EmployeePickerPanel } from './EmployeePickerPanel';

const lookupMocks = vi.hoisted(() => ({ availableNonSupervisorEmployees: vi.fn() }));

vi.mock('@/shared/api/lookupApi', () => ({
    lookupApi: lookupMocks,
}));

describe('EmployeePickerPanel', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        lookupMocks.availableNonSupervisorEmployees.mockResolvedValue({
            data: [
                { id: 21, code: 'EMP-21', name: 'First technician' },
                { id: 22, code: 'EMP-22', name: 'Second technician' },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 2 },
        });
    });

    it('renders as an inline contacts panel and selects an available employee', async () => {
        const onToggle = vi.fn();
        render(
            <EmployeePickerPanel
                lineLabel="Oil change labour"
                selectedEmployeeIds={[]}
                excludeIds={[21]}
                onClose={vi.fn()}
                onToggle={onToggle}
            />,
        );

        expect(screen.getByRole('complementary', { name: 'Employee contacts' })).toBeInTheDocument();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        await waitFor(() => expect(lookupMocks.availableNonSupervisorEmployees).toHaveBeenCalledWith(
            expect.objectContaining({ search: '', page: 1, perPage: 20 }),
        ));
        expect(screen.queryByText('First technician')).not.toBeInTheDocument();
        await userEvent.click(await screen.findByRole('button', { name: /Second technician/ }));
        expect(onToggle).toHaveBeenCalledWith({ id: 22, code: 'EMP-22', name: 'Second technician' });
    });

    it('searches employees after the user enters a term', async () => {
        render(
            <EmployeePickerPanel
                lineLabel="Oil change labour"
                selectedEmployeeIds={[]}
                excludeIds={[]}
                onClose={vi.fn()}
                onToggle={vi.fn()}
            />,
        );

        await userEvent.type(screen.getByLabelText('Search'), 'kasun');
        await waitFor(() => expect(lookupMocks.availableNonSupervisorEmployees).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: 'kasun', page: 1, perPage: 20 }),
        ));
    });
});
