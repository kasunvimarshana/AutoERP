/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import CustomerInvoicesPage from './CustomerInvoicesPage';
import * as rentalBillingApi from './rentalBillingApi';

const session = vi.hoisted(() => ({
    roles: [] as string[],
    permissions: [
        'vehicle-rental.customer-agreements.view',
        'vehicle-rental.customer-agreements.bill',
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
    previewRentalQuotation: vi.fn(),
}));

vi.mock('./agreementApi', () => ({
    searchCustomerAgreements: vi.fn(),
    getAgreement: vi.fn(),
}));

const mockSummary: rentalBillingApi.RentalBillingSummary = {
    customer_ar: {
        total_invoiced: '150000.000000',
        total_collected: '100000.000000',
        total_outstanding: '50000.000000',
        overdue_count: 2,
        overdue_amount: '20000.000000',
    },
    owner_ap: {
        total_gross_billed: '80000.000000',
        total_wht_withheld: '8000.000000',
        total_net_paid: '40000.000000',
        total_outstanding: '32000.000000',
        overdue_count: 0,
        overdue_amount: '0.000000',
    },
};

const mockDraftInvoice: rentalBillingApi.RentalBillingDocument = {
    id: 10,
    invoice_number: 'INV-DRAFT-001',
    invoice_type: 'sales',
    direction: 'outbound',
    status: 'draft',
    balance_status: 'unpaid',
    invoice_date: '2026-09-01',
    due_date: '2026-09-30',
    currency: { id: 1, code: 'LKR', symbol: 'Rs' },
    subtotal: '45000.000000',
    tax_total: '0.000000',
    discount_total: '0.000000',
    grand_total: '45000.000000',
    paid_total: '0.000000',
    credit_total: '0.000000',
    balance_due: '45000.000000',
    wht_amount: '0.000000',
    gross_amount: '45000.000000',
    party: { id: 11, type: 'customer', name: 'Global Logistics', code: 'CUST-002', phone: '0719876543' },
    agreement: { id: 101, reference: 'AGR-CUST-101', kind: 'customer', status: 'active', starts_on: '2026-09-01', ends_on: '2026-12-31' },
    vehicle: { id: 6, registration_number: 'WP-CAD-5678', make_model: 'Nissan Leaf' },
    driver_name: 'Kamal Silva',
    row_version: 1,
    created_at: '2026-09-01T10:00:00Z',
};

describe('CustomerInvoicesPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(rentalBillingApi.getRentalBillingSummary).mockResolvedValue(mockSummary);
        vi.mocked(rentalBillingApi.listRentalBillingDocuments).mockResolvedValue({
            data: [mockDraftInvoice],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
    });

    it('renders customer invoices page title and KPI metrics', async () => {
        render(<CustomerInvoicesPage />);

        await waitFor(() => {
            expect(screen.getByText('Customer Invoices (AR)')).toBeInTheDocument();
            expect(screen.getByText('Accounts Receivable')).toBeInTheDocument();
            expect(screen.getByText('INV-DRAFT-001')).toBeInTheDocument();
            expect(screen.getByText('Global Logistics')).toBeInTheDocument();
        });

        expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
            expect.objectContaining({ direction: 'outbound' }),
            expect.any(AbortSignal)
        );
    });

    it('opens quotation preview modal when Preview Quotation / Pro-Forma is clicked', async () => {
        render(<CustomerInvoicesPage />);

        const previewBtn = screen.getByRole('button', { name: /Preview Quotation \/ Pro-Forma/i });
        fireEvent.click(previewBtn);

        await waitFor(() => {
            expect(screen.getByText('Rental Quotation & Pro-Forma Preview')).toBeInTheDocument();
            expect(screen.getByRole('button', { name: 'Generate Quotation Preview' })).toBeInTheDocument();
        });
    });

    it('opens draft edit modal and submits update', async () => {
        vi.mocked(rentalBillingApi.updateRentalBillingDocument).mockResolvedValue({
            ...mockDraftInvoice,
            due_date: '2026-10-15',
            lines: [],
            adjustments: [],
            payments: [],
            telematics: null,
            financial_breakdown: {
                base_rent_amount: '45000.000000',
                excess_km_amount: '0.000000',
                overtime_amount: '0.000000',
                night_out_amount: '0.000000',
                tolls_amount: '0.000000',
                additions_amount: '0.000000',
                deductions_amount: '0.000000',
                net_subtotal: '45000.000000',
                tax_amount: '0.000000',
                wht_withheld: '0.000000',
                maintenance_deductions: '0.000000',
                grand_total: '45000.000000',
                paid_total: '0.000000',
                balance_due: '45000.000000',
            },
            statement_url: null,
            print_url: '',
            pdf_url: '',
            notes: 'Updated terms',
            cancellation_reason: null,
            can_cancel: true,
            can_edit: true,
            can_record_payment: false,
            can_print: true,
        });

        render(<CustomerInvoicesPage />);

        await waitFor(() => {
            expect(screen.getByText('INV-DRAFT-001')).toBeInTheDocument();
        });

        const editBtn = screen.getByRole('button', { name: 'Edit' });
        fireEvent.click(editBtn);

        await waitFor(() => {
            expect(screen.getByText('Edit Draft Invoice: INV-DRAFT-001')).toBeInTheDocument();
        });

        const saveBtn = screen.getByRole('button', { name: 'Save Changes' });
        fireEvent.click(saveBtn);

        await waitFor(() => {
            expect(rentalBillingApi.updateRentalBillingDocument).toHaveBeenCalledWith(10, {
                expected_version: 1,
                due_date: '2026-09-30',
                notes: null,
            });
        });
    });

    it('opens draft void modal and cancels with audit reason', async () => {
        vi.mocked(rentalBillingApi.cancelRentalBillingDocument).mockResolvedValue({
            ...mockDraftInvoice,
            status: 'cancelled',
            lines: [],
            adjustments: [],
            payments: [],
            telematics: null,
            financial_breakdown: {
                base_rent_amount: '45000.000000',
                excess_km_amount: '0.000000',
                overtime_amount: '0.000000',
                night_out_amount: '0.000000',
                tolls_amount: '0.000000',
                additions_amount: '0.000000',
                deductions_amount: '0.000000',
                net_subtotal: '45000.000000',
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
            cancellation_reason: 'Agreement terms updated',
            can_cancel: false,
            can_edit: false,
            can_record_payment: false,
            can_print: false,
        });

        render(<CustomerInvoicesPage />);

        await waitFor(() => {
            expect(screen.getByText('INV-DRAFT-001')).toBeInTheDocument();
        });

        const voidBtn = screen.getByRole('button', { name: 'Void' });
        fireEvent.click(voidBtn);

        await waitFor(() => {
            expect(screen.getByText('Void Draft Invoice: INV-DRAFT-001')).toBeInTheDocument();
        });

        const reasonInput = screen.getByPlaceholderText(/Specify why this draft is being voided/i);
        fireEvent.change(reasonInput, { target: { value: 'Contract rescheduled for next month' } });

        const confirmBtn = screen.getByRole('button', { name: 'Confirm Void Invoice' });
        fireEvent.click(confirmBtn);

        await waitFor(() => {
            expect(rentalBillingApi.cancelRentalBillingDocument).toHaveBeenCalledWith(10, {
                expected_version: 1,
                reason: 'Contract rescheduled for next month',
            });
        });
    });

    it('submits search filter and calls list API with criteria', async () => {
        render(<CustomerInvoicesPage />);

        const searchInput = screen.getByPlaceholderText(/Invoice #, vehicle reg, agreement ref/i);
        fireEvent.change(searchInput, { target: { value: 'INV-2026' } });

        const filterBtn = screen.getByRole('button', { name: 'Filter' });
        fireEvent.click(filterBtn);

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({
                    direction: 'outbound',
                    search: 'INV-2026',
                }),
                undefined
            );
        });
    });
});
