import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { NamedResource } from '@/shared/types/common';
import RentalExpensePage from './RentalExpensePage';
import type { RentalAgreementLookupOption } from '../components/RentalLookups';

const apiMocks = vi.hoisted(() => ({
    createRentalExpense: vi.fn(),
    listRentalExpenses: vi.fn(),
    transitionRentalExpense: vi.fn(),
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
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({
    VehicleLookupSelect: ({ value, onChange }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button type="button" onClick={() => onChange(vehicle())}>
            {value?.name ?? 'Choose vehicle'}
        </button>
    ),
}));
vi.mock('../components/RentalLookups', () => ({
    RentalCurrencyLookupSelect: ({ value, onChange }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button type="button" onClick={() => onChange(currency())}>
            {value?.name ?? 'Choose currency'}
        </button>
    ),
    RentalTaxGroupLookupSelect: () => (
        <div>
            <label htmlFor="tax-group">Tax group</label>
            <input id="tax-group" readOnly />
        </div>
    ),
    RentalAgreementLookupSelect: ({ value, onChange, agreementKind, disabled }: {
        value: RentalAgreementLookupOption | null;
        onChange: (value: RentalAgreementLookupOption | null) => void;
        agreementKind?: 'customer_rental' | 'owner_supply';
        disabled?: boolean;
    }) => (
        <div>
            <label htmlFor="agreement-lookup">Rental agreement</label>
            <input id="agreement-lookup" readOnly value={value?.name ?? ''} />
            <button
                type="button"
                disabled={disabled}
                onClick={() => onChange(agreementKind === 'owner_supply' ? ownerAgreement() : customerAgreement())}
            >
                Choose agreement
            </button>
            <button type="button" disabled={disabled} onClick={() => onChange(otherCustomerAgreement())}>
                Choose other agreement
            </button>
        </div>
    ),
    RentalAllocationLookupSelect: ({ value, onChange, disabled, agreementId }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
        disabled?: boolean;
        agreementId?: number | null;
    }) => (
        <div>
            <label htmlFor="allocation-lookup">Vehicle allocation</label>
            <input id="allocation-lookup" readOnly value={value?.name ?? ''} />
            <button
                type="button"
                disabled={disabled || !agreementId}
                onClick={() => onChange(allocation())}
            >
                Choose allocation
            </button>
        </div>
    ),
}));

describe('RentalExpensePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalExpenses.mockResolvedValue(collection([]));
        apiMocks.createRentalExpense.mockResolvedValue({ id: 99 });
    });

    it('requires a target agreement before customer recovery can be saved', async () => {
        const user = userEvent.setup();
        renderPage();

        await prepareRequiredFields(user);
        await user.selectOptions(screen.getByLabelText('Financial treatment'), 'customer_recovery');

        expect(screen.getByRole('button', { name: 'Save expense' })).toBeDisabled();

        await user.click(screen.getByRole('button', { name: 'Choose agreement' }));

        expect(screen.getByLabelText('Customer')).toHaveValue('Lessee Customer');
        expect(screen.getByRole('button', { name: 'Save expense' })).toBeEnabled();
    });

    it('submits recovery payload with party derived from the selected agreement', async () => {
        const user = userEvent.setup();
        renderPage();

        await prepareRequiredFields(user);
        await user.selectOptions(screen.getByLabelText('Financial treatment'), 'customer_recovery');
        await user.click(screen.getByRole('button', { name: 'Choose agreement' }));
        await user.click(screen.getByRole('button', { name: 'Choose allocation' }));
        await user.click(screen.getByRole('button', { name: 'Save expense' }));

        await waitFor(() => expect(apiMocks.createRentalExpense).toHaveBeenCalledWith(expect.objectContaining({
            vehicle_id: 12,
            currency_id: 1,
            net_amount: '100',
            allocations: [expect.objectContaining({
                allocation_type: 'customer_recovery',
                target_agreement_id: 7,
                target_vehicle_allocation_id: 31,
                customer_id: 22,
                supplier_id: null,
            })],
        })));
    });

    it('clears stale allocation and party when the selected agreement changes', async () => {
        const user = userEvent.setup();
        renderPage();

        await prepareRequiredFields(user);
        await user.selectOptions(screen.getByLabelText('Financial treatment'), 'customer_recovery');
        await user.click(screen.getByRole('button', { name: 'Choose agreement' }));
        await user.click(screen.getByRole('button', { name: 'Choose allocation' }));

        expect(screen.getByLabelText('Vehicle allocation')).toHaveValue('RVA-31');

        await user.click(screen.getByRole('button', { name: 'Choose other agreement' }));

        expect(screen.getByLabelText('Vehicle allocation')).toHaveValue('');
        expect(screen.getByLabelText('Customer')).toHaveValue('Other Lessee');
    });
});

async function prepareRequiredFields(user: ReturnType<typeof userEvent.setup>) {
    await user.click(await screen.findByRole('button', { name: 'Choose vehicle' }));
    await user.click(screen.getByRole('button', { name: 'Choose currency' }));
    await user.type(screen.getByLabelText('Net amount'), '100');
}

function renderPage() {
    return render(
        <TestRouter>
            <RentalExpensePage />
        </TestRouter>,
    );
}

function vehicle() {
    return { id: 12, code: 'VEH-12', name: 'CAR-1000', registration_number: 'CAR-1000' };
}

function currency() {
    return { id: 1, code: 'LKR', name: 'LKR' };
}

function customerAgreement(): RentalAgreementLookupOption {
    return {
        id: 7,
        code: 'RA-7',
        row_version: 1,
        agreement_kind: 'customer_rental',
        status: 'active',
        customer: { id: 22, code: 'CUS-22', name: 'Lessee Customer' },
        supplier: null,
        name: 'RA-7 - Lessee Customer',
    };
}

function otherCustomerAgreement(): RentalAgreementLookupOption {
    return {
        id: 8,
        code: 'RA-8',
        row_version: 1,
        agreement_kind: 'customer_rental',
        status: 'active',
        customer: { id: 23, code: 'CUS-23', name: 'Other Lessee' },
        supplier: null,
        name: 'RA-8 - Other Lessee',
    };
}

function ownerAgreement(): RentalAgreementLookupOption {
    return {
        id: 17,
        code: 'RO-17',
        row_version: 1,
        agreement_kind: 'owner_supply',
        status: 'active',
        customer: null,
        supplier: { id: 44, code: 'SUP-44', name: 'Vehicle Owner' },
        name: 'RO-17 - Vehicle Owner',
    };
}

function allocation() {
    return { id: 31, code: 'RVA-31', name: 'RVA-31' };
}

function collection<T>(data: T[]) {
    return {
        data,
        links: {},
        meta: { current_page: 1, last_page: 1, per_page: 50, total: data.length },
    };
}
