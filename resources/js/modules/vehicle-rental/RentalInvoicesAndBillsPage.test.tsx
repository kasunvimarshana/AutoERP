import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import React from 'react';
import RentalInvoicesAndBillsPage from './RentalInvoicesAndBillsPage';
import * as rentalBillingApi from './rentalBillingApi';
import * as agreementApi from './agreementApi';

const session = vi.hoisted(() => ({
    roles: [] as string[],
    permissions: [
        'vehicle-rental.customer-agreements.view',
        'vehicle-rental.owner-agreements.view',
        'vehicle-rental.customer-agreements.bill',
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
}));

vi.mock('./agreementApi', () => ({
    searchCustomerAgreements: vi.fn(),
    searchOwnerAgreements: vi.fn(),
    getAgreement: vi.fn(),
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

const mockArDoc: rentalBillingApi.RentalBillingDocument = {
    id: 1,
    invoice_number: 'INV-2026-0001',
    invoice_type: 'sales',
    direction: 'outbound',
    status: 'posted',
    balance_status: 'partially_paid',
    invoice_date: '2026-09-01',
    due_date: '2026-09-30',
    currency: { id: 1, code: 'LKR', symbol: 'Rs' },
    subtotal: '50000.000000',
    tax_total: '0.000000',
    discount_total: '0.000000',
    grand_total: '50000.000000',
    paid_total: '20000.000000',
    credit_total: '0.000000',
    balance_due: '30000.000000',
    wht_amount: '0.000000',
    gross_amount: '50000.000000',
    party: { id: 10, type: 'customer', name: 'Acme Logistics', code: 'CUST-001', phone: '0771234567' },
    agreement: { id: 100, reference: 'AGR-CUST-100', kind: 'customer', status: 'active', starts_on: '2026-09-01', ends_on: '2026-12-31' },
    vehicle: { id: 5, registration_number: 'WP-CAS-1234', make_model: 'Toyota Prius' },
    driver_name: 'Sunil Perera',
    row_version: 1,
    created_at: '2026-09-01T10:00:00Z',
};

const mockApDoc: rentalBillingApi.RentalBillingDocument = {
    id: 2,
    invoice_number: 'BILL-2026-0002',
    invoice_type: 'purchase',
    direction: 'inbound',
    status: 'posted',
    balance_status: 'unpaid',
    invoice_date: '2026-09-01',
    due_date: '2026-09-20',
    currency: { id: 1, code: 'LKR', symbol: 'Rs' },
    subtotal: '60000.000000',
    tax_total: '0.000000',
    discount_total: '0.000000',
    grand_total: '54000.000000',
    paid_total: '0.000000',
    credit_total: '0.000000',
    balance_due: '54000.000000',
    wht_amount: '6000.000000',
    gross_amount: '60000.000000',
    party: { id: 20, type: 'supplier', name: 'Kamal Silva (Owner)', code: 'OWN-002', phone: '0719876543' },
    agreement: { id: 200, reference: 'AGR-OWN-200', kind: 'owner', status: 'active', starts_on: '2026-09-01', ends_on: '2026-12-31' },
    vehicle: { id: 6, registration_number: 'WP-CAB-5678', make_model: 'Nissan Caravan' },
    driver_name: null,
    row_version: 1,
    created_at: '2026-09-01T11:00:00Z',
};

const mockDetail: rentalBillingApi.RentalBillingDetail = {
    ...mockArDoc,
    lines: [
        {
            id: 1,
            line_number: 1,
            description: 'Monthly Rental Base Charge',
            quantity: '1.000000',
            unit_price: '50000.000000',
            discount_amount: '0.000000',
            subtotal: '50000.000000',
            tax_total: '0.000000',
            grand_total: '50000.000000',
            line_type: 'service',
        },
    ],
    adjustments: [],
    payments: [
        {
            id: 1,
            payment_id: 88,
            payment_number: 'REC-0088',
            payment_date: '2026-09-10',
            method_name: 'Bank Transfer',
            reference: 'REF-77221',
            allocated_amount: '20000.000000',
            realized_at: '2026-09-10T12:00:00Z',
            status: 'posted',
            receipt_url: '/api/v1/payments/88/receipt',
        },
    ],
    telematics: {
        has_charts: true,
        trips_count: 2,
        commercial_km: '1500.000000',
        normal_ot_hours: '2.50',
        double_ot_hours: '1.00',
        triple_ot_hours: '0.00',
        night_outs: 1,
        vehicles: ['WP-CAB-1234'],
        drivers: ['Sunil Perera'],
    },
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
        grand_total: '50000.000000',
        paid_total: '20000.000000',
        balance_due: '30000.000000',
    },
    statement_url: '/api/v1/vehicle-rental/billing/documents/1/statement',
    print_url: '/api/v1/invoices/1/print',
    pdf_url: '/api/v1/invoices/1/pdf',
    notes: 'Initial test note',
    cancellation_reason: null,
    can_cancel: false,
    can_edit: false,
    can_record_payment: true,
    can_print: true,
};

describe('RentalInvoicesAndBillsPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(rentalBillingApi.getRentalBillingSummary).mockResolvedValue(mockSummary);
        vi.mocked(rentalBillingApi.listRentalBillingDocuments).mockResolvedValue({
            data: [mockArDoc],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        vi.mocked(rentalBillingApi.getRentalBillingDetail).mockResolvedValue(mockDetail);
    });

    it('renders the initial page with AR metrics and customer invoices table', async () => {
        render(<RentalInvoicesAndBillsPage />);

        expect(screen.getByText('Invoices & Vehicle Owner Bills')).toBeInTheDocument();
        expect(screen.getByText('Customer Invoices (AR)')).toBeInTheDocument();
        expect(screen.getByText('Vehicle Owner Bills (AP)')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('INV-2026-0001')).toBeInTheDocument();
            expect(screen.getByText('Acme Logistics')).toBeInTheDocument();
            expect(screen.getByText('WP-CAS-1234')).toBeInTheDocument();
        });

        // AR metrics visible
        expect(screen.getByText('Total Invoiced (AR)')).toBeInTheDocument();
        expect(screen.getByText('Total Collected')).toBeInTheDocument();
        expect(screen.getByText('Outstanding Receivables')).toBeInTheDocument();
        expect(screen.getByText('Overdue Receivables')).toBeInTheDocument();
    });

    it('switches between Customer Invoices tab and Vehicle Owner Bills tab', async () => {
        vi.mocked(rentalBillingApi.listRentalBillingDocuments).mockResolvedValueOnce({
            data: [mockArDoc],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        }).mockResolvedValueOnce({
            data: [mockApDoc],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });

        render(<RentalInvoicesAndBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('INV-2026-0001')).toBeInTheDocument();
        });

        // Switch to AP tab
        const apTab = screen.getByRole('button', { name: /Vehicle Owner Bills \(AP\)/i });
        fireEvent.click(apTab);

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({ direction: 'inbound' }),
                expect.any(AbortSignal)
            );
            expect(screen.getByText('BILL-2026-0002')).toBeInTheDocument();
            expect(screen.getByText('Kamal Silva (Owner)')).toBeInTheDocument();
            expect(screen.getByText('Gross Rent Billed (AP)')).toBeInTheDocument();
            expect(screen.getByText('10% WHT Withheld')).toBeInTheDocument();
        });
    });

    it('filters documents when search or status changes', async () => {
        render(<RentalInvoicesAndBillsPage />);

        const searchInput = screen.getByLabelText('Search');
        fireEvent.change(searchInput, { target: { value: 'Acme' } });

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({ search: 'Acme' }),
                expect.any(AbortSignal)
            );
        });

        const statusSelect = screen.getByLabelText('Document Status');
        fireEvent.change(statusSelect, { target: { value: 'posted' } });

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({ status: 'posted' }),
                expect.any(AbortSignal)
            );
        });
    });

    it('opens document detail modal when View is clicked', async () => {
        render(<RentalInvoicesAndBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('INV-2026-0001')).toBeInTheDocument();
        });

        const viewButton = screen.getByRole('button', { name: 'View' });
        fireEvent.click(viewButton);

        await waitFor(() => {
            expect(rentalBillingApi.getRentalBillingDetail).toHaveBeenCalledWith(1);
            expect(screen.getByText('Monthly Rental Base Charge')).toBeInTheDocument();
            expect(screen.getByText('Payment Allocations & Receipts')).toBeInTheDocument();
            expect(screen.getByText('REC-0088')).toBeInTheDocument();
        });
    });

    it('opens billing cycle creation modal when + Billing Cycle Invoice is clicked', async () => {
        render(<RentalInvoicesAndBillsPage />);

        const createCycleBtn = screen.getByRole('button', { name: '+ Billing Cycle Invoice' });
        fireEvent.click(createCycleBtn);

        await waitFor(() => {
            expect(screen.getByText('Generate Consolidated Billing Cycle Invoice')).toBeInTheDocument();
            expect(screen.getByText('Consolidated Billing Cycle')).toBeInTheDocument();
            expect(screen.getByText('Advance Base Rent')).toBeInTheDocument();
        });
    });

    it('opens owner settlement modal when + Owner Settlement Bill is clicked', async () => {
        render(<RentalInvoicesAndBillsPage />);

        const createOwnerBillBtn = screen.getByRole('button', { name: '+ Owner Settlement Bill' });
        fireEvent.click(createOwnerBillBtn);

        await waitFor(() => {
            expect(screen.getByText('Generate Inbound AP Supplier Bill')).toBeInTheDocument();
            expect(screen.getByLabelText('Settlement Period Start')).toBeInTheDocument();
            expect(screen.getByLabelText('Settlement Period End')).toBeInTheDocument();
            expect(screen.getByLabelText('WHT Rate (%)')).toBeInTheDocument();
        });
    });

    it('hides billing creation buttons when user has only view permissions', async () => {
        session.permissions = [
            'vehicle-rental.customer-agreements.view',
            'vehicle-rental.owner-agreements.view',
        ];

        render(<RentalInvoicesAndBillsPage />);

        await waitFor(() => {
            expect(screen.getByText('Invoices & Vehicle Owner Bills')).toBeInTheDocument();
        });

        expect(screen.queryByRole('button', { name: '+ Advance Base Rent' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: '+ Billing Cycle Invoice' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: '+ Owner Settlement Bill' })).not.toBeInTheDocument();

        // Restore full permissions for subsequent tests
        session.permissions = [
            'vehicle-rental.customer-agreements.view',
            'vehicle-rental.owner-agreements.view',
            'vehicle-rental.customer-agreements.bill',
            'vehicle-rental.owner-agreements.bill',
        ];
    });

    it('defaults to AP tab and hides AR tab when user only has owner view permission', async () => {
        session.permissions = [
            'vehicle-rental.owner-agreements.view',
            'vehicle-rental.owner-agreements.bill',
        ];

        vi.mocked(rentalBillingApi.listRentalBillingDocuments).mockResolvedValue({
            data: [mockApDoc],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });

        render(<RentalInvoicesAndBillsPage />);

        await waitFor(() => {
            expect(rentalBillingApi.listRentalBillingDocuments).toHaveBeenCalledWith(
                expect.objectContaining({ direction: 'inbound' }),
                expect.any(AbortSignal)
            );
            expect(screen.getByText('BILL-2026-0002')).toBeInTheDocument();
        });

        expect(screen.queryByText('Customer Invoices (AR)')).not.toBeInTheDocument();
        expect(screen.getByText('Vehicle Owner Bills (AP)')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: '+ Billing Cycle Invoice' })).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: '+ Owner Settlement Bill' })).toBeInTheDocument();

        // Restore permissions
        session.permissions = [
            'vehicle-rental.customer-agreements.view',
            'vehicle-rental.owner-agreements.view',
            'vehicle-rental.customer-agreements.bill',
            'vehicle-rental.owner-agreements.bill',
        ];
    });
});
