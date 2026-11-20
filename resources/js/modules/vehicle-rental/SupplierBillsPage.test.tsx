import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import SupplierBillsPage from './SupplierBillsPage';
import * as rentalBillingApi from './rentalBillingApi';

const session = vi.hoisted(() => ({
    roles: [] as string[],
    permissions: [
        'vehicle-rental.owner-agreements.view',
        'vehicle-rental.owner-agreements.bill',
    ] as string[],
    permissionsLoaded: true,
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => session,
}));

vi.mock('./rentalBillingApi', () => ({
    listRentalBillingDocuments: vi.fn(),
    getRentalBillingSummary: vi.fn(),
    getRentalBillingDetail: vi.fn(),
    updateRentalBillingDocument: vi.fn(),
    cancelRentalBillingDocument: vi.fn(),
    previewRentalOwnerBill: vi.fn(),
}));

vi.mock('./agreementApi', () => ({
    searchOwnerAgreements: vi.fn(),
    generateOwnerSettlementBill: vi.fn(),
}));

const mockSummary: rentalBillingApi.RentalBillingSummary = {
    customer_ar: {
        total_invoiced: '120000.000000',
        total_collected: '80000.000000',
        total_outstanding: '40000.000000',
        overdue_count: 1,
        overdue_amount: '15000.000000',
    },
    owner_ap: {
        total_gross_billed: '90000.000000',
        total_wht_withheld: '9000.000000',
        total_net_paid: '50000.000000',
        total_outstanding: '31000.000000',
        overdue_count: 0,
        overdue_amount: '0.000000',
    },
};

const mockDraftOwnerBill: rentalBillingApi.RentalBillingDocument = {
    id: 20,
    invoice_number: 'BILL-DRAFT-002',
    invoice_type: 'purchase',
    direction: 'inbound',
    status: 'draft',
    balance_status: 'unpaid',
    invoice_date: '2026-09-01',
    due_date: '2026-09-20',
    currency: { id: 1, code: 'LKR', symbol: 'Rs' },
    subtotal: '50000.000000',
    tax_total: '0.000000',
    discount_total: '0.000000',
    grand_total: '45000.000000',
    paid_total: '0.000000',
    credit_total: '0.000000',
    balance_due: '45000.000000',
    wht_amount: '5000.000000',
    gross_amount: '50000.000000',
    party: { id: 15, type: 'owner', name: 'Nimal Jayawardena', code: 'OWN-001', phone: '0777654321' },
    agreement: { id: 202, reference: 'AGR-OWN-202', kind: 'owner', status: 'active', starts_on: '2026-09-01', ends_on: '2026-12-31' },
    vehicle: { id: 8, registration_number: 'WP-CAR-9999', make_model: 'Toyota KDH' },
    driver_name: null,
    row_version: 1,
    created_at: '2026-09-01T10:00:00Z',
};

describe('SupplierBillsPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(rentalBillingApi.getRentalBillingSummary).mockResolvedValue(mockSummary);
        vi.mocked(rentalBillingApi.listRentalBillingDocuments).mockResolvedValue({
            data: [mockDraftOwnerBill],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
    });

    it('renders supplier bills page title, WHT KPI metrics, and owner bill record', async () => {
        render(<SupplierBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('Vehicle Owner & Supplier Bills (AP)')).toBeInTheDocument();
            expect(screen.getByText('Accounts Payable')).toBeInTheDocument();
            expect(screen.getByText('BILL-DRAFT-002')).toBeInTheDocument();
            expect(screen.getByText('Nimal Jayawardena')).toBeInTheDocument();
            expect(screen.getByText('WP-CAR-9999')).toBeInTheDocument();
        });

        expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
            expect.objectContaining({ direction: 'inbound' }),
            expect.any(AbortSignal)
        );
    });

    it('opens owner settlement preview modal when Preview Pro-Forma Settlement is clicked', async () => {
        render(<SupplierBillsPage />);

        const previewBtn = screen.getByRole('button', { name: /Preview Pro-Forma Settlement/i });
        fireEvent.click(previewBtn);

        await waitFor(() => {
            expect(screen.getByText('Vehicle Owner Bill & Settlement Preview')).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Generate Settlement Preview' })).toBeInTheDocument();
        });
    });

    it('opens draft edit modal and submits update', async () => {
        vi.mocked(rentalBillingApi.updateRentalBillingDocument).mockResolvedValue({
            ...mockDraftOwnerBill,
            due_date: '2026-10-10',
            lines: [],
            adjustments: [],
            payments: [],
            telematics: null,
            financial_breakdown: {
                base_rent_amount: '50000.000000',
                excess_km_amount: '0.000000',
                overtime_amount: '0.000000',
                night_out_amount: '0.000000',
                tolls_amount: '0.000000',
                additions_amount: '0.000000',
                deductions_amount: '0.000000',
                net_subtotal: '50000.000000',
                tax_amount: '0.000000',
                wht_withheld: '5000.000000',
                maintenance_deductions: '0.000000',
                grand_total: '45000.000000',
                paid_total: '0.000000',
                balance_due: '45000.000000',
            },
            statement_url: null,
            print_url: '',
            pdf_url: '',
            notes: 'Bank transfer requested',
            cancellation_reason: null,
            can_cancel: true,
            can_edit: true,
            can_record_payment: false,
            can_print: true,
        });

        render(<SupplierBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('BILL-DRAFT-002')).toBeInTheDocument();
        });

        const editBtn = screen.getByRole('button', { name: 'Edit' });
        fireEvent.click(editBtn);

        await waitFor(() => {
            expect(screen.getByText('Edit Draft Bill: BILL-DRAFT-002')).toBeInTheDocument();
        });

        const saveBtn = screen.getByRole('button', { name: 'Save Changes' });
        fireEvent.click(saveBtn);

        await waitFor(() => {
            expect(rentalBillingApi.updateRentalBillingDocument).toHaveBeenCalledWith(20, {
                expected_version: 1,
                due_date: '2026-09-20',
                notes: null,
            });
        });
    });

    it('opens draft void modal and cancels with audit reason', async () => {
        vi.mocked(rentalBillingApi.cancelRentalBillingDocument).mockResolvedValue({
            ...mockDraftOwnerBill,
            status: 'cancelled',
            lines: [],
            adjustments: [],
            payments: [],
            telematics: null,
            financial_breakdown: {
                base_rent_amount: '50000.000000',
                excess_km_amount: '0.000000',
                overtime_amount: '0.000000',
                night_out_amount: '0.000000',
                tolls_amount: '0.000000',
                additions_amount: '0.000000',
                deductions_amount: '0.000000',
                net_subtotal: '50000.000000',
                tax_amount: '0.000000',
                wht_withheld: '0.000000',
                maintenance_deductions: '0.000000',
                grand_total: '45000.000000',
                paid_total: '0.000000',
                balance_due: '0.000000',
            },
            statement_url: null,
            print_url: '',
            pdf_url: '',
            notes: null,
            cancellation_reason: 'Incorrect settlement period entered',
            can_cancel: false,
            can_edit: false,
            can_record_payment: false,
            can_print: false,
        });

        render(<SupplierBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('BILL-DRAFT-002')).toBeInTheDocument();
        });

        const voidBtn = screen.getByRole('button', { name: 'Void' });
        fireEvent.click(voidBtn);

        await waitFor(() => {
            expect(screen.getByText('Void Draft Bill: BILL-DRAFT-002')).toBeInTheDocument();
        });

        const reasonInput = screen.getByPlaceholderText(/Specify why this draft settlement is being voided/i);
        fireEvent.change(reasonInput, { target: { value: 'Date range overlap requires correction' } });

        const confirmBtn = screen.getByRole('button', { name: 'Confirm Void Bill' });
        fireEvent.click(confirmBtn);

        await waitFor(() => {
            expect(rentalBillingApi.cancelRentalBillingDocument).toHaveBeenCalledWith(20, {
                expected_version: 1,
                reason: 'Date range overlap requires correction',
            });
        });
    });

    it('submits search filter and renders maintenance deductions and balance due columns', async () => {
        render(<SupplierBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('Deductions')).toBeInTheDocument();
            expect(screen.getByText('Balance Due')).toBeInTheDocument();
        });

        const searchInput = screen.getByPlaceholderText(/Bill #, vehicle reg, agreement ref/i);
        fireEvent.change(searchInput, { target: { value: 'BILL-999' } });

        const filterBtn = screen.getByRole('button', { name: 'Filter' });
        fireEvent.click(filterBtn);

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({
                    direction: 'inbound',
                    search: 'BILL-999',
                }),
                undefined
            );
        });
    });
});
