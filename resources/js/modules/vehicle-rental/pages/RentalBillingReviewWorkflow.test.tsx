import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalBillingPage from './RentalBillingPage';

const apiMocks = vi.hoisted(() => ({
    calculateRentalAgreement: vi.fn(),
    createRentalInvoice: vi.fn(),
    listRentalCalculationRuns: vi.fn(),
    transitionRentalCalculationRun: vi.fn(),
}));

vi.mock('../vehicleRentalApi', () => apiMocks);
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => true }));
vi.mock('../components/RentalPage', () => ({ RentalPage: ({ children }: { children: ReactNode }) => children }));
vi.mock('../components/RentalLookups', () => ({
    RentalAgreementLookupSelect: () => <div>Agreement lookup</div>,
}));

const submittedRun = {
    id: 41,
    row_version: 3,
    billing_period: {
        id: 11,
        row_version: 2,
        agreement: { id: 7, name: 'LES-0007', row_version: 4 },
        financial_side: 'revenue',
        period_start: '2026-07-01T00:00:00.000Z',
        period_end: '2026-07-31T00:00:00.000Z',
        status: 'open',
    },
    run_version: 1,
    currency: { id: 1, name: 'Sri Lankan Rupee', code: 'LKR', symbol: 'Rs.' },
    calculation_status: 'submitted',
    document_status: 'not_generated',
    net_total: '125000.000000',
    discount_total: '0.000000',
    tax_total: '22500.000000',
    withholding_total: '0.000000',
    grand_total: '147500.000000',
    sources: [],
    lines: [{
        id: 101,
        row_version: 1,
        line_number: 1,
        source_type: 'usage_context',
        source: {
            type: 'usage_context',
            usage_context: {
                id: 55,
                financial_side: 'revenue',
                usage: { id: 66, name: 'RUL-0066', usage_number: 'RUL-0066', status: 'approved' },
                usage_fact: { id: 77, row_version: 2, status: 'approved' },
            },
        },
        component_code: 'excess_km',
        description: 'Excess kilometre recovery',
        measured_quantity: '1250.000000',
        allowed_quantity: '1000.000000',
        chargeable_quantity: '250.000000',
        unit: 'km',
        rate: '500.000000',
        multiplier: '1.000000',
        net_amount: '125000.000000',
        discount_amount: '0.000000',
        tax_amount: '22500.000000',
        withholding_amount: '0.000000',
        total_amount: '147500.000000',
        applied_rule: 'period',
        status: 'draft',
    }],
};

describe('Rental billing review workflow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalCalculationRuns.mockResolvedValue({
            data: [submittedRun],
            links: {},
            meta: {
                current_page: 1,
                from: 1,
                last_page: 1,
                per_page: 50,
                to: 1,
                total: 1,
            },
        });
        apiMocks.transitionRentalCalculationRun.mockResolvedValue({
            ...submittedRun,
            row_version: 4,
            calculation_status: 'approved',
        });
    });

    it('requires opening the detailed calculation review before approval is offered', async () => {
        render(
            <MemoryRouter>
                <RentalBillingPage />
            </MemoryRouter>,
        );

        const review = await screen.findByRole('button', { name: 'Review' });
        expect(screen.queryByRole('button', { name: 'Approve calculation' })).not.toBeInTheDocument();

        await userEvent.click(review);

        expect(screen.getByText('Lessee invoice calculation review')).toBeInTheDocument();
        expect(screen.getByText('Excess kilometre recovery')).toBeInTheDocument();
        expect(screen.getByText('RUL-0066')).toBeInTheDocument();
        expect(screen.getByText('250.000000 km')).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Approve calculation' }));

        await waitFor(() => expect(apiMocks.transitionRentalCalculationRun).toHaveBeenCalledWith(
            submittedRun.id,
            submittedRun.row_version,
            'approved',
        ));
    });
});
