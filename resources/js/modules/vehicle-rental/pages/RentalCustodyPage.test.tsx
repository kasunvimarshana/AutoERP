import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalCustodyPage from './RentalCustodyPage';
import type { NamedResource } from '@/shared/types/common';

const apiMocks = vi.hoisted(() => ({
    confirmRentalCustodyEvent: vi.fn(),
    createRentalCustodyEvent: vi.fn(),
    getRentalAllocation: vi.fn(),
    getRentalMetadata: vi.fn(),
    listRentalCustodyEvents: vi.fn(),
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
    RentalAllocationLookupSelect: ({ value }: { value: NamedResource | null }) => (
        <div>
            <label htmlFor="allocation-lookup">Vehicle allocation</label>
            <input id="allocation-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
}));

describe('RentalCustodyPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalAllocation.mockResolvedValue(ownerSupplyAllocation());
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.listRentalCustodyEvents.mockResolvedValue(collection([]));
        apiMocks.createRentalCustodyEvent.mockResolvedValue({ id: 9 });
        apiMocks.confirmRentalCustodyEvent.mockResolvedValue({ ...draftCustodyEvent(), status: 'confirmed' });
    });

    it('uses owner custody event types for owner supply allocations', async () => {
        const user = userEvent.setup();
        renderPage('/vehicle-rental/custody?allocation_id=31');

        const eventType = await screen.findByLabelText('Event type') as HTMLSelectElement;

        expect(eventType).toHaveValue('owner_to_company');
        expect(screen.getByLabelText('From')).toHaveValue('owner');
        expect(screen.getByLabelText('To')).toHaveValue('company');
        expect(screen.getByLabelText('Odometer')).toHaveValue(null);
        expect(screen.queryByRole('option', { name: 'Company To Customer' })).not.toBeInTheDocument();
        expect(screen.queryByRole('option', { name: 'Replacement Out' })).not.toBeInTheDocument();
        expect(screen.queryByRole('option', { name: 'Replacement In' })).not.toBeInTheDocument();

        await user.type(screen.getByLabelText('Odometer'), '1000');
        await user.click(screen.getByRole('button', { name: 'Save custody event' }));

        await waitFor(() => expect(apiMocks.createRentalCustodyEvent).toHaveBeenCalledWith(31, 1, expect.objectContaining({
            event_type: 'owner_to_company',
            odometer: '1000',
            from_role: 'owner',
            to_role: 'company',
            fuel_level_percent: null,
            location: null,
            condition_summary: null,
            damage_summary: null,
        })));
    });

    it('confirms custody against the loaded allocation version', async () => {
        const user = userEvent.setup();
        apiMocks.listRentalCustodyEvents.mockResolvedValue(collection([draftCustodyEvent()]));
        renderPage('/vehicle-rental/custody?allocation_id=31');

        const table = await screen.findByRole('table');
        await user.click(within(table).getByRole('button', { name: 'Confirm' }));

        await waitFor(() => expect(apiMocks.confirmRentalCustodyEvent).toHaveBeenCalledWith(91, 4, 1));
    });
});

function renderPage(path: string) {
    return render(
        <TestRouter initialEntries={[path]}>
            <RentalCustodyPage />
        </TestRouter>,
    );
}

function ownerSupplyAllocation() {
    return {
        id: 31,
        row_version: 1,
        allocation_number: 'RVA-31',
        agreement: {
            id: 8,
            agreement_number: 'RA-8',
            agreement_kind: 'owner_supply',
            status: 'active',
        },
        vehicle: {
            id: 12,
            code: 'VEH-12',
            name: 'CAR-1000',
            registration_number: 'CAR-1000',
            status: 'active',
        },
        vehicle_source_type: 'owner_supplied',
        source_allocation: null,
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

function draftCustodyEvent() {
    return {
        id: 91,
        row_version: 4,
        event_number: 'RCE-91',
        allocation: null,
        replacement: null,
        vehicle: {
            id: 12,
            code: 'VEH-12',
            name: 'CAR-1000',
            registration_number: 'CAR-1000',
            status: 'active',
        },
        event_type: 'owner_to_company',
        occurred_at: '2026-07-10T08:00:00.000Z',
        odometer: '1000.000000',
        fuel_level_percent: null,
        location: null,
        from_role: 'owner',
        to_role: 'company',
        condition_summary: null,
        damage_summary: null,
        status: 'draft',
        items: [],
    };
}

function rentalMetadata() {
    return {
        public_custody_event_types: [
            'owner_to_company',
            'company_to_customer',
            'customer_to_company',
            'company_to_owner',
            'internal_transfer',
        ],
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
