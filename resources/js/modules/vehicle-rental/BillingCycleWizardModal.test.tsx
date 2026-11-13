import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { BillingCycleWizardModal } from './BillingCycleWizardModal';
import { AgreementKind, AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, type Agreement, type TermKey } from './agreements';
import * as agreementApi from './agreementApi';

vi.mock('./agreementApi', () => ({
    createBillingCycle: vi.fn(),
    getBillingCycle: vi.fn(),
    attachCycleCharts: vi.fn(),
    addCycleItem: vi.fn(),
    removeCycleItem: vi.fn(),
    previewCycleFinal: vi.fn(),
    finalizeBillingCycle: vi.fn(),
}));

const mockAgreement: Agreement = {
    id: 10,
    reference: 'AG-CUST-2026-001',
    row_version: 1,
    status: AgreementStatus.Active,
    basis: RentalBasis.Monthly,
    driver_mode: DriverMode.WithDriver,
    party: { id: 1, name: 'Apex Commercial' },
    currency: { id: 1, name: 'US Dollar', code: 'USD' },
    agreed_on: '2026-09-01',
    executing_on: null,
    starts_on: '2026-09-01',
    ends_on: '2026-09-30',
    notes: null,
    terms: Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>,
};

const mockExistingCycle: agreementApi.BillingCycle = {
    id: 25,
    cycle_number: 'BC-202609-0001',
    customer_agreement_id: 10,
    period_from: '2026-09-01',
    period_until: '2026-09-30',
    status: 'stage1_generated',
    billing_mode: 'monthly',
    finalization_mode: 'supplementary',
    base_rent_amount: '45000.000000',
    excess_km_amount: '0.000000',
    overtime_amount: '0.000000',
    night_out_amount: '0.000000',
    tolls_amount: '0.000000',
    additions_amount: '0.000000',
    deductions_amount: '0.000000',
    net_subtotal: '45000.000000',
    tax_amount: '0.000000',
    total_billed_amount: '45000.000000',
    total_paid_amount: '0.000000',
    row_version: 1,
    initial_invoice: {
        id: 77,
        invoice_number: 'INV-2026-0077',
        grand_total: '45000.000000',
        status: 'issued',
    },
    final_invoice: null,
    supplementary_invoice: null,
    charts: [],
    items: [],
};

describe('BillingCycleWizardModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the initial cycle creation form and calls createBillingCycle on submit', async () => {
        const viCreate = vi.mocked(agreementApi.createBillingCycle).mockResolvedValue({
            ...mockExistingCycle,
        });

        render(
            <BillingCycleWizardModal
                open={true}
                onClose={vi.fn()}
                kind={AgreementKind.Customer}
                agreement={mockAgreement}
                cycle={null}
            />,
        );

        expect(screen.getByText(/New Billing Cycle for AG-CUST-2026-001/i)).toBeInTheDocument();
        expect(screen.getByText(/Issue Initial Base Rent Invoice \(Stage 1\) Immediately/i)).toBeInTheDocument();

        const submitBtn = screen.getByRole('button', { name: /Initialize Billing Cycle/i });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(viCreate).toHaveBeenCalledWith(
                AgreementKind.Customer,
                10,
                expect.objectContaining({
                    billing_mode: 'monthly',
                    finalization_mode: 'supplementary',
                    issue_initial_invoice: true,
                }),
            );
        });
    });

    it('renders existing cycle management with tabs and allows switching tabs', async () => {
        vi.mocked(agreementApi.previewCycleFinal).mockResolvedValue({
            cycle_id: 25,
            cycle_number: 'BC-202609-0001',
            status: 'stage1_generated',
            period_from: '2026-09-01',
            period_until: '2026-09-30',
            billing_mode: 'monthly',
            finalization_mode: 'supplementary',
            base_rent_amount: '45000.000000',
            excess_km_amount: '5000.000000',
            overtime_amount: '2000.000000',
            night_out_amount: '1000.000000',
            tolls_amount: '500.000000',
            additions_amount: '8500.000000',
            deductions_amount: '0.000000',
            net_subtotal: '53500.000000',
            tax_amount: '0.000000',
            total_billed_amount: '53500.000000',
            initial_invoice: {
                id: 77,
                invoice_number: 'INV-2026-0077',
                grand_total: '45000.000000',
                paid_total: '0.000000',
                balance_due: '45000.000000',
            },
            gross_delta_amount: '8500.000000',
            charts_count: 1,
            items_count: 0,
        });

        render(
            <BillingCycleWizardModal
                open={true}
                onClose={vi.fn()}
                kind={AgreementKind.Customer}
                agreement={mockAgreement}
                cycle={mockExistingCycle}
            />,
        );

        expect(screen.getByText(/Billing Cycle BC-202609-0001/i)).toBeInTheDocument();
        expect(screen.getByText(/INV-2026-0077/i)).toBeInTheDocument();

        // Switch to items tab
        const itemsTab = screen.getByRole('button', { name: /Additions & Deductions/i });
        fireEvent.click(itemsTab);
        expect(screen.getByText(/Add Line Item Adjustment/i)).toBeInTheDocument();

        // Switch to finalize tab
        const finalizeTab = screen.getByRole('button', { name: /Finalize/i });
        fireEvent.click(finalizeTab);

        await waitFor(() => {
            expect(screen.getByText(/Final Financial Breakdown Preview/i)).toBeInTheDocument();
            expect(screen.getByText(/Delta to Settle/i)).toBeInTheDocument();
        });
    });
});
