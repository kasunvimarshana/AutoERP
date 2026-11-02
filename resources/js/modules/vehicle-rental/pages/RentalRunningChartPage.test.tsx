import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalRunningChartPage from './RentalRunningChartPage';
import type { NamedResource } from '@/shared/types/common';

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
    RentalAllocationLookupSelect: ({
        value,
        onChange,
    }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <div>
            <label htmlFor="allocation-lookup">Vehicle allocation</label>
            <input
                id="allocation-lookup"
                readOnly
                value={value?.name ?? ''}
                data-testid="allocation-lookup-input"
            />
            <button
                type="button"
                data-testid="select-mock-allocation"
                onClick={() =>
                    onChange({
                        id: 42,
                        code: 'RVA-42',
                        name: 'RVA-42 - SEDAN-2000 - planned',
                    })
                }
            >
                Select Mock Allocation
            </button>
        </div>
    ),
}));

describe('RentalRunningChartPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(metadataResponse());
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('planned'));
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.createRentalUsageLog.mockResolvedValue({ id: 101, row_version: 1 });
    });

    it('displays planned allocation details and vehicle handover guidance without rendering usage form', async () => {
        renderPage('/vehicle-rental/running-chart?allocation_id=42');

        await waitFor(() => {
            expect(apiMocks.getRentalAllocation).toHaveBeenCalledWith(42, expect.any(AbortSignal));
        });

        const lookupInput = await screen.findByTestId('allocation-lookup-input') as HTMLInputElement;
        expect(lookupInput.value).toBe('RVA-42 - SEDAN-2000 - planned');

        expect(screen.getByText('Allocation details')).toBeInTheDocument();
        expect(screen.getByText('SEDAN-2000')).toBeInTheDocument();
        expect(screen.getByText('Vehicle handover required')).toBeInTheDocument();
        expect(screen.getByText('Allocation is currently Planned')).toBeInTheDocument();

        const handoverLink = screen.getByRole('link', { name: 'Handover vehicle now' });
        expect(handoverLink).toHaveAttribute('href', '/vehicle-rental/custody?allocation_id=42');

        expect(screen.queryByRole('button', { name: 'Save physical usage' })).not.toBeInTheDocument();
    });

    it('displays active allocation, pre-fills start odometer and allows recording physical usage', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('active', '15000.000000'));
        const user = userEvent.setup();
        renderPage('/vehicle-rental/running-chart?allocation_id=42');

        await waitFor(() => {
            expect(apiMocks.getRentalAllocation).toHaveBeenCalledWith(42, expect.any(AbortSignal));
        });

        expect(await screen.findByText('Physical usage')).toBeInTheDocument();
        expect(screen.queryByText('Vehicle handover required')).not.toBeInTheDocument();

        await waitFor(() => {
            const startOdometer = screen.getByLabelText('Start odometer') as HTMLInputElement;
            expect(startOdometer.value).toBe('15000.000000');
        });

        const driverSelect = screen.getByLabelText('Valid driver assignment') as HTMLSelectElement;
        expect(driverSelect).toBeInTheDocument();
        expect(screen.getByRole('option', { name: /John Driver/ })).toBeInTheDocument();

        await user.type(screen.getByLabelText('Actual start'), '2026-07-15T08:00');
        await user.type(screen.getByLabelText('Actual finish'), '2026-07-15T17:00');
        await user.type(screen.getByLabelText('End odometer'), '15200');

        await user.click(screen.getByRole('button', { name: 'Save physical usage' }));

        await waitFor(() => {
            expect(apiMocks.createRentalUsageLog).toHaveBeenCalledWith(42, expect.objectContaining({
                start_odometer: '15000.000000',
                end_odometer: '15200',
            }));
        });
    });

    it('pre-fills start odometer from the latest usage log end odometer when logs exist', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('active', '15000.000000'));
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([
            {
                id: 1,
                usage_number: 'RUL-001',
                end_odometer: '15320.000000',
                distance_km: '320',
                net_operational_distance_km: '320',
                status: 'approved',
                usage_date: '2026-07-12',
                contexts: [],
            },
        ]));

        renderPage('/vehicle-rental/running-chart?allocation_id=42');

        await waitFor(() => {
            expect(apiMocks.getRentalAllocation).toHaveBeenCalledWith(42, expect.any(AbortSignal));
        });

        await waitFor(() => {
            const startOdometer = screen.getByLabelText('Start odometer') as HTMLInputElement;
            expect(startOdometer.value).toBe('15320.000000');
        });
    });

    it('displays allocation closed notice for completed allocations without entry form', async () => {
        apiMocks.getRentalAllocation.mockResolvedValue(customerRentalAllocation('completed', '15000.000000'));
        renderPage('/vehicle-rental/running-chart?allocation_id=42');

        await waitFor(() => {
            expect(apiMocks.getRentalAllocation).toHaveBeenCalledWith(42, expect.any(AbortSignal));
        });

        expect(await screen.findByText('Allocation closed')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Save physical usage' })).not.toBeInTheDocument();
    });
});

function renderPage(path: string) {
    return render(
        <TestRouter initialEntries={[path]}>
            <RentalRunningChartPage />
        </TestRouter>,
    );
}

function metadataResponse() {
    return {
        usage_event_types: ['parking', 'toll', 'fuel', 'damage'],
        usage_event_applicabilities: ['customer', 'owner', 'both', 'internal'],
    };
}

function customerRentalAllocation(
    status: 'planned' | 'active' | 'completed' = 'planned',
    startOdometer = '15000.000000',
) {
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
        drivers: [
            {
                id: 7,
                employee: { id: 101, name: 'John Driver' },
                assignment_role: 'primary',
                assigned_from: '2026-07-10T08:00:00.000Z',
                assigned_to: '2026-08-10T08:00:00.000Z',
                status: 'active',
            },
        ],
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
