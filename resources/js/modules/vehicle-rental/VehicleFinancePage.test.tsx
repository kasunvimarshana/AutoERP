import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleFinancePage from './VehicleFinancePage';
import {
    FinanceStatus,
    InstallmentStatus,
    type VehicleFinanceAgreement,
} from './vehicleFinanceApi';
import * as vehicleFinanceApi from './vehicleFinanceApi';

const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./vehicleFinanceApi', async (importOriginal) => {
    const actual = await importOriginal<typeof vehicleFinanceApi>();
    return {
        ...actual,
        listFinanceAgreements: vi.fn(),
        getFinanceAgreement: vi.fn(),
        activateFinanceAgreement: vi.fn(),
        createInstallmentPayable: vi.fn(),
        refreshFinanceDueStatuses: vi.fn(),
    };
});

const mockAgreement: VehicleFinanceAgreement = {
    id: 1,
    row_version: 1,
    agreement_number: 'FIN-2026-0001',
    supplier: { id: 15, name: 'Commercial Leasing & Finance' },
    vehicle: { id: 30, label: 'WP-NC-5678 (Toyota HiAce)', make: 'Toyota', model: 'HiAce' },
    currency: { id: 1, code: 'LKR' },
    status: FinanceStatus.Draft,
    agreement_date: '2026-11-01',
    starts_at: '2026-11-01T00:00:00Z',
    matures_at: '2027-11-01T00:00:00Z',
    principal_amount: '1200000.00',
    initial_deposit_amount: '200000.00',
    residual_value: '0.00',
    interest_method: 'flat',
    annual_interest_rate: '12.00',
    installment_frequency: 'monthly',
    installment_count: 12,
    payment_term_days: 30,
    remarks: 'Fleet expansion HP lease',
    created_at: '2026-11-01 10:00:00',
    installments: [
        {
            id: 101,
            row_version: 1,
            finance_agreement_id: 1,
            installment_number: 1,
            due_date: '2026-12-01',
            status: InstallmentStatus.Scheduled,
            principal_due: '83333.33',
            interest_due: '10000.00',
            fee_due: '0.00',
            tax_due: '0.00',
            total_due: '93333.33',
            paid_amount: '0.00',
            balance_due: '93333.33',
            invoice: null,
        },
    ],
};

beforeEach(() => {
    vi.clearAllMocks();
    session.permissions = ['vehicle-rental.finance.view'];
    vi.mocked(vehicleFinanceApi.listFinanceAgreements).mockResolvedValue({
        data: [mockAgreement],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
    });
    vi.mocked(vehicleFinanceApi.getFinanceAgreement).mockResolvedValue(mockAgreement);
});

describe('VehicleFinancePage', () => {
    it('renders finance agreement list in read-only mode for view-only users', async () => {
        render(<VehicleFinancePage />);
        expect(await screen.findByText('FIN-2026-0001')).toBeInTheDocument();
        expect(screen.getByText('Commercial Leasing & Finance')).toBeInTheDocument();
        expect(screen.getByText('WP-NC-5678 (Toyota HiAce)')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'New Finance Agreement' })).not.toBeInTheDocument();
    });

    it('allows manager to activate draft agreement and view installments schedule', async () => {
        session.permissions = ['vehicle-rental.finance.view', 'vehicle-rental.finance.manage'];
        vi.mocked(vehicleFinanceApi.activateFinanceAgreement).mockResolvedValue({
            ...mockAgreement,
            status: FinanceStatus.Active,
            row_version: 2,
        });

        render(<VehicleFinancePage />);
        expect(await screen.findByRole('button', { name: 'New Finance Agreement' })).toBeInTheDocument();

        // Click Installments (12)
        const reviewBtn = screen.getByRole('button', { name: 'Installments (12)' });
        fireEvent.click(reviewBtn);

        expect(await screen.findByText(/Amortization Schedule/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Activate Agreement' })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Activate Agreement' }));
        await waitFor(() => {
            expect(vehicleFinanceApi.activateFinanceAgreement).toHaveBeenCalledWith(1, 1);
        });
    });

    it('allows manager to generate payable invoice for an installment', async () => {
        session.permissions = ['vehicle-rental.finance.view', 'vehicle-rental.finance.manage'];
        const activeAgreement: VehicleFinanceAgreement = {
            ...mockAgreement,
            status: FinanceStatus.Active,
            installments: [
                {
                    ...mockAgreement.installments![0],
                    status: InstallmentStatus.Due,
                },
            ],
        };
        vi.mocked(vehicleFinanceApi.listFinanceAgreements).mockResolvedValue({
            data: [activeAgreement],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
        });
        vi.mocked(vehicleFinanceApi.getFinanceAgreement).mockResolvedValue(activeAgreement);
        vi.mocked(vehicleFinanceApi.createInstallmentPayable).mockResolvedValue({
            id: 505,
        });

        render(<VehicleFinancePage />);
        const reviewBtn = await screen.findByRole('button', { name: 'Installments (12)' });
        fireEvent.click(reviewBtn);

        const createPayableBtn = await screen.findByRole('button', { name: 'Generate Invoice' });
        fireEvent.click(createPayableBtn);

        await waitFor(() => {
            expect(vehicleFinanceApi.createInstallmentPayable).toHaveBeenCalledWith(101, 1, expect.any(String));
        });
    });
});
