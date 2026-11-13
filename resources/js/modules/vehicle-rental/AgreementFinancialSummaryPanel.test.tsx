import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { AgreementFinancialSummaryPanel } from './AgreementFinancialSummaryPanel';
import {
    getAgreementFinancialSummary,
    createAgreementFinancialAdjustment,
    type AgreementFinancialSummary,
} from './agreementApi';
import {
    AgreementKind,
    AgreementStatus,
    DriverMode,
    RentalBasis,
    TERM_LABELS,
    type Agreement,
    type TermKey,
} from './agreements';

vi.mock('./agreementApi', () => ({
    getAgreementFinancialSummary: vi.fn(),
    createAgreementFinancialAdjustment: vi.fn(),
    listBillingCycles: vi.fn().mockResolvedValue({ data: [] }),
    listReassignments: vi.fn().mockResolvedValue([]),
}));

const mockAgreement: Agreement = {
    id: 12,
    reference: 'AG-CUST-100',
    row_version: 3,
    status: AgreementStatus.Active,
    basis: RentalBasis.Monthly,
    driver_mode: DriverMode.SelfDrive,
    party: { id: 5, name: 'Acme Logistics' },
    currency: { id: 1, name: 'US Dollar', code: 'USD' },
    agreed_on: '2026-09-01',
    executing_on: null,
    starts_on: '2026-09-07',
    ends_on: null,
    notes: null,
    terms: Object.fromEntries(Object.keys(TERM_LABELS).map(key => [key, null])) as Record<TermKey, string | null>,
};

const mockSummaryData: AgreementFinancialSummary = {
    agreement: {
        id: 12,
        reference: 'AG-CUST-100',
        kind: 'customer',
        status: 'active',
        currency: { id: 1, code: 'USD' },
        party: { id: 5, name: 'Acme Logistics', code: 'CUST-05' },
    },
    summary: {
        total_base_charges: '50000.000000',
        total_usage_charges: '5000.000000',
        total_allocations: '1200.000000',
        total_invoiced: '56200.000000',
        total_credit_notes: '2000.000000',
        total_debit_notes: '1200.000000',
        total_paid: '40000.000000',
        total_credit_allocated: '1000.000000',
        total_remaining_balance: '15200.000000',
        total_unallocated_credit: '1000.000000',
    },
    base_charges: [],
    usage_charges: [],
    allocations: [
        {
            id: 101,
            row_version: 1,
            sequence: 1,
            allocation_type: 'customer_recovery',
            allocation_type_label: 'Customer Recovery',
            net_amount: '1200.000000',
            total_amount: '1200.000000',
            status: 'confirmed',
            expense: {
                id: 45,
                expense_number: 'EXP-45',
                expense_type: 'Toll',
                expense_date: '2026-09-15',
            },
            invoice: {
                id: 88,
                invoice_number: 'INV-0088',
                status: 'issued',
            },
        },
    ],
    documents: [
        {
            id: 88,
            invoice_number: 'INV-0088',
            invoice_date: '2026-09-16',
            due_date: '2026-09-30',
            invoice_type: 'debit',
            document_kind: 'debit_note',
            status: 'issued',
            grand_total: '1200.000000',
            paid_total: '0.000000',
            credit_total: '0.000000',
            balance_due: '1200.000000',
            notes: 'Highway toll recovery',
        },
    ],
    deposit: {
        requirement: '5000.000000',
        net_receipts: '5000.000000',
        remaining: '0.000000',
        receipts_count: 1,
    },
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(getAgreementFinancialSummary).mockResolvedValue(mockSummaryData);
});

it('renders financial summary metrics and linked documents', async () => {
    render(
        <AgreementFinancialSummaryPanel
            kind={AgreementKind.Customer}
            agreement={mockAgreement}
            canManageAdjustments={true}
        />,
    );

    await waitFor(() => {
        expect(screen.getByText('Linked Financial Documents')).toBeInTheDocument();
    });

    expect(screen.getByText('Total Invoiced')).toBeInTheDocument();
    expect(screen.getAllByText('INV-0088')).toHaveLength(2);
    expect(screen.getByText('EXP-45')).toBeInTheDocument();
    expect(screen.getByText('Customer Recovery')).toBeInTheDocument();
    expect(screen.getByText('+ Issue Credit / Debit Note')).toBeInTheDocument();
});

it('allows issuing an adjustment credit note and reloads summary', async () => {
    vi.mocked(createAgreementFinancialAdjustment).mockResolvedValue({
        id: 99,
        invoice_number: 'CN-0099',
    });

    render(
        <AgreementFinancialSummaryPanel
            kind={AgreementKind.Customer}
            agreement={mockAgreement}
            canManageAdjustments={true}
        />,
    );

    await waitFor(() => {
        expect(screen.getByText('+ Issue Credit / Debit Note')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByText('+ Issue Credit / Debit Note'));

    expect(screen.getByText('Issue Financial Adjustment for AG-CUST-100')).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/Amount/), { target: { value: '2500.00' } });
    fireEvent.change(screen.getByLabelText(/Reason/), { target: { value: 'Breakdown downtime compensation' } });

    fireEvent.click(screen.getByRole('button', { name: /Confirm & Issue Credit Note/ }));

    await waitFor(() => {
        expect(createAgreementFinancialAdjustment).toHaveBeenCalledWith(
            AgreementKind.Customer,
            12,
            expect.objectContaining({
                expected_version: 3,
                adjustment_type: 'credit_note',
                amount: '2500.00',
                reason: 'Breakdown downtime compensation',
            }),
        );
    });

    expect(await screen.findByText(/Successfully issued Credit Note CN-0099/)).toBeInTheDocument();
});

it('hides adjustment controls when user lacks manage permissions', async () => {
    render(
        <AgreementFinancialSummaryPanel
            kind={AgreementKind.Customer}
            agreement={mockAgreement}
            canManageAdjustments={false}
        />,
    );

    await waitFor(() => {
        expect(screen.getByText('Linked Financial Documents')).toBeInTheDocument();
    });

    expect(screen.queryByText('+ Issue Credit / Debit Note')).not.toBeInTheDocument();
});
