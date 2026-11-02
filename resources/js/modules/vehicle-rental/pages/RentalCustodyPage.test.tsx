import { render, screen, waitFor } from '@testing-library/react';
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
        apiMocks.listRentalCustodyEvents.mockResolvedValue(collection([]));
        apiMocks.createRentalCustodyEvent.mockResolvedValue({ id: 9, row_version: 1 });
        apiMocks.confirmRentalCustodyEvent.mockResolvedValue({ id: 9, row_version: 2 });
    });

    it('uses owner custody event types for owner supply allocations', async () => {
        const user = userEvent.setup();
        renderPage('/vehicle-rental/custody?allocation_id=31');

        const eventType = await screen.findByLabelText('Event type') as HTMLSelectElement;

        expect(eventType).toHaveValue('owner_to_company');
        expect(screen.getByLabelText('From')).toHaveValue('owner');
        expect(screen.getByLabelText('To')).toHaveValue('company');
        expect(screen.queryByRole('option', { name: 'company to customer' })).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Save custody event' }));

        await waitFor(() => expect(apiMocks.createRentalCustodyEvent).toHaveBeenCalledWith(31, expect.objectContaining({
            event_type: 'owner_to_company',
            from_role: 'owner',
            to_role: 'company',
            fuel_level_percent: null,
            location: null,
            condition_summary: null,
            damage_summary: null,
        })));
    });

    it('handles customer rental handover with start odometer prefill and immediate confirmation', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('planned', '15000.000000'));
        const user = userEvent.setup();
        renderPage('/vehicle-rental/custody?allocation_id=42');

        const eventType = await screen.findByLabelText('Event type') as HTMLSelectElement;
        const odometer = screen.getByLabelText('Odometer') as HTMLInputElement;

        expect(eventType).toHaveValue('company_to_customer');
        expect(odometer).toHaveValue(15000);
        expect(screen.getByLabelText('From')).toHaveValue('company');
        expect(screen.getByLabelText('To')).toHaveValue('customer');
        expect(screen.getByText('Handover vehicle to customer')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Save & confirm' }));

        await waitFor(() => expect(apiMocks.createRentalCustodyEvent).toHaveBeenCalledWith(42, expect.objectContaining({
            event_type: 'company_to_customer',
            from_role: 'company',
            to_role: 'customer',
            odometer: '15000.000000',
        })));

        await waitFor(() => expect(apiMocks.confirmRentalCustodyEvent).toHaveBeenCalledWith(9, 1));
    });

    it('defaults active customer allocation to return event type without offering initial handover', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('active', '15000.000000'));
        renderPage('/vehicle-rental/custody?allocation_id=42');

        const eventType = await screen.findByLabelText('Event type') as HTMLSelectElement;

        expect(eventType).toHaveValue('customer_to_company');
        expect(screen.getByLabelText('From')).toHaveValue('customer');
        expect(screen.getByLabelText('To')).toHaveValue('company');
        expect(screen.queryByRole('option', { name: 'company to customer' })).not.toBeInTheDocument();
        expect(screen.getByText('Return vehicle from customer')).toBeInTheDocument();
    });

    it('shows owner receipt guidance banner and disables handover when source owner allocation is planned', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue({
            ...customerRentalAllocation('planned', '15000.000000'),
            vehicle_source_type: 'owner_supplied',
            source_allocation: {
                id: 31,
                allocation_number: 'RVA-31',
                name: 'RVA-31',
                status: 'planned',
            },
        });
        renderPage('/vehicle-rental/custody?allocation_id=42');

        expect(await screen.findByText('Owner vehicle receipt required before customer handover')).toBeInTheDocument();
        expect(screen.getByText(/This vehicle is owner-supplied via allocation/)).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Receive vehicle from owner first' })).toHaveAttribute(
            'href',
            '/vehicle-rental/custody?allocation_id=31',
        );

        const saveConfirmButton = screen.getByRole('button', { name: 'Save & confirm' });
        const saveButton = screen.getByRole('button', { name: 'Save custody event' });
        expect(saveConfirmButton).toBeDisabled();
        expect(saveButton).toBeDisabled();
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

function customerRentalAllocation(status = 'planned', startOdometer = '0.000000') {
    return {
        id: 42,
        row_version: 1,
        allocation_number: 'RVA-42',
        agreement: {
            id: 10,
            agreement_number: 'RA-10',
            agreement_kind: 'customer_rental',
            status: 'active',
        },
        vehicle: {
            id: 15,
            code: 'VEH-15',
            name: 'SEDAN-2000',
            registration_number: 'SEDAN-2000',
            status: 'active',
        },
        vehicle_source_type: 'company_owned',
        source_allocation: null,
        allocated_from: '2026-07-10T08:00:00.000Z',
        allocated_to: '2026-08-10T08:00:00.000Z',
        actual_returned_at: null,
        start_odometer: startOdometer,
        end_odometer: null,
        status,
        remarks: null,
        drivers: [],
        custody_events: [],
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
