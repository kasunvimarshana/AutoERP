import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { RentalAvailableVehicleLookupSelect } from './RentalLookups';

const rentalApiMocks = vi.hoisted(() => ({
    listAvailableRentalVehicles: vi.fn(),
    listRentalAgreements: vi.fn(),
    listRentalAllocations: vi.fn(),
    listVehicleFinanceAgreements: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => rentalApiMocks);
vi.mock('@/modules/invoice/invoiceApi', () => ({ listInvoices: vi.fn() }));
vi.mock('@/modules/payment/paymentApi', () => ({ listPaymentMethods: vi.fn() }));
vi.mock('@/modules/tax/taxApi', () => ({ listTaxGroups: vi.fn() }));
vi.mock('@/shared/api/referenceApi', () => ({ searchCurrencies: vi.fn() }));

describe('RentalAvailableVehicleLookupSelect', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        rentalApiMocks.listAvailableRentalVehicles.mockResolvedValue(collection([
            {
                id: 12,
                vehicle_number: 'VEH-1000',
                code: 'VEH-1000',
                registration_number: 'CAR-1000',
                status: 'active',
            },
        ]));
    });

    it('loads available vehicles when opened for a valid allocation period', async () => {
        const user = userEvent.setup();
        const startAt = '2026-07-20T08:00:00.000Z';
        const endAt = '2026-07-21T08:00:00.000Z';

        render(
            <RentalAvailableVehicleLookupSelect
                value={null}
                onChange={() => undefined}
                startAt={startAt}
                endAt={endAt}
            />,
        );

        await user.click(screen.getByRole('combobox', { name: 'Vehicle' }));

        await waitFor(() => expect(rentalApiMocks.listAvailableRentalVehicles).toHaveBeenCalledWith(
            {
                search: '',
                page: 1,
                per_page: 20,
                start_at: startAt,
                end_at: endAt,
            },
            expect.any(AbortSignal),
        ));
        expect(
            await screen.findByRole('option', { name: 'VEH-1000 - VEH-1000' }),
        ).toBeInTheDocument();
    });
});

function collection<T>(data: T[]) {
    return {
        data,
        links: {},
        meta: {
            current_page: 1,
            from: data.length ? 1 : null,
            last_page: 1,
            per_page: 20,
            to: data.length,
            total: data.length,
        },
    };
}
