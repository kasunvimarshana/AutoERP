import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import type { NamedResource } from '@/shared/types/common';
import RentalRunningChartPage from './RentalRunningChartPage';

const apiMocks = vi.hoisted(() => ({
    createRentalUsageLog: vi.fn(),
    getRentalAllocation: vi.fn(),
    getRentalMetadata: vi.fn(),
    listRentalUsageLogs: vi.fn(),
    transitionRentalUsageLog: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({}),
}));
vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: () => true,
}));
vi.mock('../vehicleRentalApi', () => apiMocks);
vi.mock('../components/RentalPage', () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));
vi.mock('../components/RentalLookups', () => ({
    RentalAllocationLookupSelect: ({ value, label }: {
        value: NamedResource | null;
        label?: string;
    }) => (
        <div>
            <label htmlFor="allocation-lookup">{label ?? 'Vehicle allocation'}</label>
            <input id="allocation-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
}));

describe('RentalRunningChartPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalAllocation.mockResolvedValue(allocation());
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
    });

    it('uses the shared select placeholder for event type without a duplicate blank option', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-rental/running-chart?allocation_id=31']}>
                <RentalRunningChartPage />
            </TestRouter>,
        );

        await waitFor(() => expect(apiMocks.getRentalAllocation).toHaveBeenCalledWith(31, expect.any(AbortSignal)));
        await user.click(await screen.findByRole('button', { name: 'Add event' }));

        const eventSelect = screen.getByLabelText('Event 1') as HTMLSelectElement;
        const blankOptions = Array.from(eventSelect.options).filter((option) => option.value === '');
        expect(blankOptions).toHaveLength(1);
        expect(Array.from(eventSelect.options).map((option) => option.value)).toContain('parking');
    });
});

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
            rental_mode: 'self_drive',
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
        status: 'active',
        remarks: null,
        drivers: [],
        custody_events: [],
    };
}

function rentalMetadata() {
    return {
        usage_event_types: ['parking', 'toll'],
        usage_event_applicabilities: ['customer', 'owner', 'both', 'internal'],
    };
}

function collection<T>(data: T[]) {
    return {
        data,
        links: {},
        meta: {
            current_page: 1,
            from: data.length ? 1 : null,
            last_page: 1,
            per_page: 50,
            to: data.length,
            total: data.length,
        },
    };
}
