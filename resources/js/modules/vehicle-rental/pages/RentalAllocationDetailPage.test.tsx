import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalAllocationDetailPage from './RentalAllocationDetailPage';

const apiMocks = vi.hoisted(() => ({
    assignRentalDriver: vi.fn(),
    cancelRentalAllocation: vi.fn(),
    getRentalAllocation: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => apiMocks);
vi.mock('../components/RentalPage', () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));
vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({
        label,
        value,
        onChange,
        formatLabel,
    }: {
        label: string;
        value: { id: number; employee_number?: string; display_name?: string; name?: string } | null;
        onChange: (value: { id: number; employee_number: string; display_name: string; name: string }) => void;
        formatLabel: (value: { id: number; employee_number?: string; display_name?: string; name?: string }) => string;
    }) => {
        const driver = {
            id: 22,
            employee_number: 'EMP-22',
            display_name: 'Test Driver',
            name: 'Test Driver',
        };

        return (
            <div>
                <label htmlFor="driver-lookup">{label}</label>
                <input
                    id="driver-lookup"
                    readOnly
                    value={value ? formatLabel(value) : ''}
                />
                <button type="button" onClick={() => onChange(driver)}>
                    Choose driver
                </button>
            </div>
        );
    },
}));

describe('RentalAllocationDetailPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalAllocation.mockResolvedValue(allocation());
        apiMocks.assignRentalDriver.mockResolvedValue({ id: 9 });
    });

    it('submits driver assignment dates as ISO instants', async () => {
        const user = userEvent.setup();
        const row = allocation();
        const expectedFrom = localDateTime(row.allocated_from);
        const expectedTo = localDateTime(row.allocated_to);

        renderPage('/vehicle-rental/allocations/31');

        const from = await screen.findByLabelText('From') as HTMLInputElement;
        const to = screen.getByLabelText('To') as HTMLInputElement;

        expect(from).toHaveValue(expectedFrom);
        expect(to).toHaveValue(expectedTo);

        await user.click(screen.getByRole('button', { name: 'Choose driver' }));
        await user.click(screen.getByRole('button', { name: 'Assign driver' }));

        await waitFor(() => expect(apiMocks.assignRentalDriver).toHaveBeenCalledWith(
            31,
            5,
            expect.objectContaining({
                employee_id: 22,
                assigned_from: new Date(expectedFrom).toISOString(),
                assigned_to: new Date(expectedTo).toISOString(),
                is_primary: true,
            }),
        ));
    });
});

function renderPage(path: string) {
    return render(
        <MemoryRouter initialEntries={[path]}>
            <Routes>
                <Route path="/vehicle-rental/allocations/:id" element={<RentalAllocationDetailPage />} />
            </Routes>
        </MemoryRouter>,
    );
}

function localDateTime(value: string | null | undefined): string {
    if (!value) return '';

    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

function allocation() {
    return {
        id: 31,
        row_version: 5,
        allocation_number: 'RVA-31',
        agreement: {
            id: 8,
            row_version: 3,
            agreement_number: 'RA-8',
            agreement_kind: 'customer_rental',
            status: 'active',
        },
        vehicle: {
            id: 12,
            code: 'VEH-12',
            name: 'CAR-1000',
            registration_number: 'CAR-1000',
            status: 'active',
        },
        ownership: null,
        vehicle_source_type: 'company_owned',
        source_allocation: null,
        finance_agreement: null,
        allocated_from: '2026-07-10T08:00:00.000Z',
        allocated_to: '2026-08-10T08:00:00.000Z',
        actual_returned_at: null,
        start_odometer: '0.000000',
        end_odometer: null,
        status: 'planned',
        remarks: null,
        drivers: [],
        custody_events: [],
    };
}
