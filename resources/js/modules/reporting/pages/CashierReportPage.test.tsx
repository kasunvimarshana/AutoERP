/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import CashierReportPage from './CashierReportPage';
import type { CashierReportResult } from '../cashierReportTypes';

const apiMocks = vi.hoisted(() => ({
    runCashierReport: vi.fn(),
    loadCashiersList: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: () => <div data-testid="export-actions">Export Actions Mock</div>,
}));

const mockReportData: CashierReportResult = {
    period: {
        date_from: '2026-11-23',
        date_to: '2026-11-23',
    },
    organization_unit: 'Colombo Central Workshop',
    currency: 'LKR',
    summary: {
        total_inflow: '150.00',
        total_outflow: '20.00',
        net_collections: '130.00',
        cash_inflow: '110.00',
        cash_outflow: '20.00',
        net_cash_in_drawer: '90.00',
        non_cash_total: '40.00',
        total_transactions: 3,
        inbound_count: 2,
        outbound_count: 1,
    },
    payment_methods: [
        {
            key: 'CASH',
            name: 'Cash Currency',
            type: 'cash',
            inflow: '110.00',
            inflow_count: 2,
            outflow: '20.00',
            outflow_count: 1,
            net: '90.00',
            total_count: 3,
        },
        {
            key: 'CARD',
            name: 'Visa Card',
            type: 'card',
            inflow: '40.00',
            inflow_count: 1,
            outflow: '0.00',
            outflow_count: 0,
            net: '40.00',
            total_count: 1,
        },
    ],
    cashiers: [
        {
            cashier_id: 101,
            cashier_name: 'Alice Teller',
            cashier_email: 'alice@autoerp.test',
            inflow: '100.00',
            outflow: '20.00',
            net: '80.00',
            cash_net: '40.00',
            non_cash_net: '40.00',
            transaction_count: 2,
        },
        {
            cashier_id: 102,
            cashier_name: 'Bob Cashier',
            cashier_email: 'bob@autoerp.test',
            inflow: '50.00',
            outflow: '0.00',
            net: '50.00',
            cash_net: '50.00',
            non_cash_net: '0.00',
            transaction_count: 1,
        },
    ],
    transactions: {
        data: [
            {
                id: 1,
                payment_number: 'RCP-2026-0001',
                payment_date: '2026-11-23',
                created_at: '2026-11-23T09:30:00Z',
                direction: 'inbound',
                direction_label: 'Collection',
                payment_type: 'customer_receipt',
                document_status: 'approved',
                posting_status: 'posted',
                currency_code: 'LKR',
                total_amount: '100.00',
                party_name: 'Customer John Doe',
                reference_number: 'REF-001',
                cashier_id: 101,
                cashier_name: 'Alice Teller',
                organization_unit_name: 'Colombo Central Workshop',
                lines: [
                    {
                        method_code: 'CASH',
                        method_name: 'Cash',
                        method_type: 'cash',
                        amount: '60.00',
                        reference_number: '',
                        instrument_number: '',
                    },
                    {
                        method_code: 'CARD',
                        method_name: 'Visa',
                        method_type: 'card',
                        amount: '40.00',
                        reference_number: '',
                        instrument_number: 'AUTH-999',
                    },
                ],
                allocations: [
                    {
                        invoice_number: 'INV-2026-0042',
                        allocated_amount: '100.00',
                    },
                ],
            },
        ],
        meta: {
            current_page: 1,
            from: 1,
            last_page: 1,
            per_page: 25,
            to: 1,
            total: 1,
        },
    },
};

describe('CashierReportPage', () => {
    it('renders the cashier report with KPI cards, tender methods, and detailed transactions', async () => {
        apiMocks.runCashierReport.mockResolvedValue(mockReportData);
        apiMocks.loadCashiersList.mockResolvedValue([
            { id: 101, name: 'Alice Teller', email: 'alice@autoerp.test' },
            { id: 102, name: 'Bob Cashier', email: 'bob@autoerp.test' },
        ]);

        render(
            <TestRouter>
                <CashierReportPage />
            </TestRouter>,
        );

        // Header
        expect(await screen.findByRole('heading', { name: 'Cashier Report' })).toBeInTheDocument();
        expect(screen.getByTestId('export-actions')).toBeInTheDocument();

        // Organization Unit and Period
        expect(screen.getByText('Colombo Central Workshop')).toBeInTheDocument();
        expect(screen.getByText(/Reporting Period: 2026-11-23 to 2026-11-23/)).toBeInTheDocument();

        // KPI Metric Cards
        expect(screen.getByText('💵 Net Cash In Drawer')).toBeInTheDocument();
        expect(screen.getByText(/90\.00/)).toBeInTheDocument();
        expect(screen.getByText(/150\.00/)).toBeInTheDocument();
        expect(screen.getAllByText(/20\.00/).length).toBeGreaterThan(0);
        expect(screen.getByText(/130\.00/)).toBeInTheDocument();
        expect(screen.getAllByText(/40\.00/).length).toBeGreaterThan(0);

        // Detailed Transactions Table (Active Tab)
        expect(screen.getByText('RCP-2026-0001')).toBeInTheDocument();
        expect(screen.getByText('Customer John Doe')).toBeInTheDocument();
        expect(screen.getByText('Alice Teller')).toBeInTheDocument();
        expect(screen.getByText(/Cash:/)).toBeInTheDocument();
        expect(screen.getByText(/Visa:/)).toBeInTheDocument();
        expect(screen.getByText(/INV-2026-0042/)).toBeInTheDocument();
        expect(screen.getAllByText(/100\.00/).length).toBeGreaterThan(0);
    });

    it('switches between tabs to view payment methods breakdown and cashier summary', async () => {
        const user = userEvent.setup();
        apiMocks.runCashierReport.mockResolvedValue(mockReportData);
        apiMocks.loadCashiersList.mockResolvedValue([]);

        render(
            <TestRouter>
                <CashierReportPage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Cashier Report' })).toBeInTheDocument();

        // Switch to Payment Method Breakdown
        const methodTabBtn = screen.getByRole('button', { name: /Payment Method Breakdown/ });
        await user.click(methodTabBtn);

        expect(screen.getByText('Cash Currency')).toBeInTheDocument();
        expect(screen.getByText('Visa Card')).toBeInTheDocument();
        expect(screen.getAllByText(/110\.00/).length).toBeGreaterThan(0);

        // Switch to Cashier Summary
        const cashierTabBtn = screen.getByRole('button', { name: /Cashier Summary/ });
        await user.click(cashierTabBtn);

        expect(screen.getByText('Alice Teller')).toBeInTheDocument();
        expect(screen.getByText('Bob Cashier')).toBeInTheDocument();
        expect(screen.getByText('alice@autoerp.test')).toBeInTheDocument();
    });

    it('handles empty transaction results gracefully', async () => {
        apiMocks.runCashierReport.mockResolvedValue({
            ...mockReportData,
            summary: {
                total_inflow: '0.00',
                total_outflow: '0.00',
                net_collections: '0.00',
                cash_inflow: '0.00',
                cash_outflow: '0.00',
                net_cash_in_drawer: '0.00',
                non_cash_total: '0.00',
                total_transactions: 0,
                inbound_count: 0,
                outbound_count: 0,
            },
            payment_methods: [],
            cashiers: [],
            transactions: {
                data: [],
                meta: {
                    current_page: 1,
                    from: 0,
                    last_page: 1,
                    per_page: 25,
                    to: 0,
                    total: 0,
                },
            },
        });

        render(
            <TestRouter>
                <CashierReportPage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Cashier Report' })).toBeInTheDocument();
        expect(screen.getByText('No cashier transactions found matching the selected filters.')).toBeInTheDocument();
    });
});
