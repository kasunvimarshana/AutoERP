import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalAllocationPage from './RentalAllocationPage';
import type { NamedResource } from '@/shared/types/common';

const apiMocks = vi.hoisted(() => ({
    createRentalAllocation: vi.fn(),
    getRentalAgreement: vi.fn(),
    listRentalAllocations: vi.fn(),
}));

const vehicleApiMocks = vi.hoisted(() => ({
    getVehicle: vi.fn(),
}));

const vehicleOwnershipApiMocks = vi.hoisted(() => ({
    listVehicleOwnerships: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => apiMocks);
vi.mock('@/modules/vehicle/vehicleApi', () => vehicleApiMocks);
vi.mock('@/modules/vehicle/vehicleOwnershipApi', () => vehicleOwnershipApiMocks);
vi.mock('../components/RentalPage', () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({
    VehicleLookupSelect: ({ value }: { value: NamedResource | null }) => (
        <div>
            <label htmlFor="vehicle-lookup">Vehicle</label>
            <input id="vehicle-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
}));
vi.mock('../components/RentalLookups', () => ({
    RentalAgreementLookupSelect: ({ value }: { value: NamedResource | null }) => (
        <div>
            <label htmlFor="agreement-lookup">Rental agreement</label>
            <input id="agreement-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
    RentalAllocationLookupSelect: ({ value }: { value: NamedResource | null }) => (
        <div>
            <label htmlFor="source-allocation-lookup">Vehicle allocation</label>
            <input id="source-allocation-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
    RentalFinanceAgreementLookupSelect: ({ value }: { value: NamedResource | null }) => (
        <div>
            <label htmlFor="finance-agreement-lookup">Finance agreement</label>
            <input id="finance-agreement-lookup" readOnly value={value?.name ?? ''} />
        </div>
    ),
}));

describe('RentalAllocationPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalAgreement.mockResolvedValue(rentalAgreement());
        apiMocks.listRentalAllocations.mockResolvedValue(collection([]));
        apiMocks.createRentalAllocation.mockResolvedValue({ id: 99 });
        vehicleApiMocks.getVehicle.mockResolvedValue({
            id: 12,
            code: 'VEH-12',
            status: 'active',
            name: 'CAR-1000',
            registration_number: 'CAR-1000',
        });
        vehicleOwnershipApiMocks.listVehicleOwnerships.mockResolvedValue(collection([]));
    });

    it('prefills and submits allocation dates from the loaded agreement period', async () => {
        const user = userEvent.setup();
        renderPage('/vehicle-rental/allocations?agreement_id=7&vehicle_id=12');

        const from = await screen.findByLabelText('From') as HTMLInputElement;
        const to = screen.getByLabelText('To') as HTMLInputElement;
        const expectedFrom = localDateTime(rentalAgreement().starts_at);
        const expectedTo = localDateTime(rentalAgreement().ends_at);

        expect(from).toHaveValue(expectedFrom);
        expect(from).toHaveAttribute('min', expectedFrom);
        expect(from).toHaveAttribute('max', expectedTo);
        expect(to).toHaveValue(expectedTo);
        expect(to).toHaveAttribute('min', expectedFrom);
        expect(to).toHaveAttribute('max', expectedTo);

        await user.click(screen.getByRole('button', { name: 'Create allocation' }));

        await waitFor(() => expect(apiMocks.createRentalAllocation).toHaveBeenCalledWith(7, expect.objectContaining({
            vehicle_id: 12,
            vehicle_source_type: 'company_owned',
            allocated_from: new Date(expectedFrom).toISOString(),
            allocated_to: new Date(expectedTo).toISOString(),
            start_odometer: '0',
        })));
    });

    it('creates owner supply allocations directly from the supplier vehicle ownership', async () => {
        apiMocks.getRentalAgreement.mockResolvedValue(ownerSupplyAgreement());
        vehicleOwnershipApiMocks.listVehicleOwnerships.mockResolvedValue(collection([supplierOwnership()]));
        const user = userEvent.setup();
        renderPage('/vehicle-rental/allocations?agreement_id=8&vehicle_id=12');

        const ownership = await screen.findByLabelText('Owner vehicle ownership') as HTMLInputElement;
        const expectedFrom = localDateTime(ownerSupplyAgreement().starts_at);
        const expectedTo = localDateTime(ownerSupplyAgreement().ends_at);

        await waitFor(() => expect(ownership).toHaveValue('CAR-1000 - Owner Supplier - owned'));
        expect(screen.getByLabelText('Vehicle source')).toHaveValue('owner_supplied');

        await user.click(screen.getByRole('button', { name: 'Create allocation' }));

        await waitFor(() => expect(vehicleOwnershipApiMocks.listVehicleOwnerships).toHaveBeenCalledWith('supplier', expect.objectContaining({
            supplier_id: 33,
            vehicle_id: 12,
            status: 'active',
            per_page: 100,
        }), expect.any(AbortSignal)));
        await waitFor(() => expect(apiMocks.createRentalAllocation).toHaveBeenCalledWith(8, expect.objectContaining({
            vehicle_id: 12,
            vehicle_ownership_id: 31,
            vehicle_source_type: 'owner_supplied',
            source_allocation_id: null,
            allocated_from: new Date(expectedFrom).toISOString(),
            allocated_to: new Date(expectedTo).toISOString(),
            start_odometer: '0',
        })));
    });
});

function renderPage(path: string) {
    return render(
        <TestRouter initialEntries={[path]}>
            <RentalAllocationPage />
        </TestRouter>,
    );
}

function localDateTime(value: string): string {
    const date = new Date(value);
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

function rentalAgreement() {
    return {
        id: 7,
        row_version: 1,
        agreement_number: 'RA-7',
        agreement_kind: 'customer_rental',
        customer: { id: 4, name: 'Acme Customer' },
        supplier: null,
        agreement_date: '2026-07-05',
        starts_at: '2026-07-05T08:00:00.000Z',
        ends_at: '2026-08-05T08:00:00.000Z',
        legal_context: 'company',
        rental_mode: 'with_driver',
        billing_cycle: 'monthly',
        billing_basis: 'calendar_month',
        proration_rule: 'exact_day_count',
        billing_timezone: 'UTC',
        payment_term_days: 30,
        currency: { id: 1, code: 'LKR', name: 'Sri Lankan Rupee' },
        status: 'active',
        remarks: null,
        active_rate_version: null,
        rate_versions: [],
        allocations: [],
        deposit_requirement: null,
    };
}

function ownerSupplyAgreement() {
    return {
        ...rentalAgreement(),
        id: 8,
        agreement_number: 'RA-8',
        agreement_kind: 'owner_supply',
        customer: null,
        supplier: { id: 33, name: 'Owner Supplier' },
        starts_at: '2026-07-10T08:00:00.000Z',
        ends_at: '2026-08-10T08:00:00.000Z',
    };
}

function supplierOwnership() {
    return {
        id: 31,
        row_version: 1,
        owner_type: 'supplier',
        owner: { id: 33, code: 'SUP-33', name: 'Owner Supplier' },
        customer: null,
        supplier: { id: 33, code: 'SUP-33', number: 'SUP-33', name: 'Owner Supplier', status: 'snapshot' },
        vehicle: {
            id: 12,
            number: 'VEH-12',
            registration_number: 'CAR-1000',
            chassis_number: null,
            make: null,
            model: null,
        },
        organization: null,
        relationship_type: 'owned',
        ownership_type: 'owned',
        started_at: '2026-07-01T00:00:00.000Z',
        ended_at: '2026-09-01T00:00:00.000Z',
        is_current: true,
        notes: null,
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
            per_page: 25,
            to: data.length,
            total: data.length,
        },
    };
}
