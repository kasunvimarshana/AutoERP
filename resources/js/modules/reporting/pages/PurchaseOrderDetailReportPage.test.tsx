import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PurchaseOrderDetailReportPage from './PurchaseOrderDetailReportPage';

const apiMocks = vi.hoisted(() => ({ runPurchaseOrderDetailReport: vi.fn() }));
vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({ ExportActions: () => <div>Export actions</div> }));
vi.mock('@/modules/purchase/components/PurchaseLookups', () => ({ SupplierLookupSelect: () => <div>Supplier lookup</div> }));

describe('PurchaseOrderDetailReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runPurchaseOrderDetailReport.mockResolvedValue({
            report: { title: 'Purchase Order Detail Report' },
            data: [{
                id: 10,
                purchase_order_number: 'PO-001',
                purchase_order_date: '2026-10-05',
                status: 'approved',
                status_action: { label: 'Approved by', actor: { name: 'Nimali Perera' } },
                supplier: { id: 4, name: 'R F Marketing Services', code: 'RF' },
                warehouse: 'Main',
                currency: 'LKR',
                po_amount: '1500.000000',
                grn_cost: '810.170000',
                ordered_quantity: '7.000000',
                grn_quantity: '5.000000',
                uom: 'PCS',
                items: [
                    { id: 1, description: 'Brake oil', item_code: 'BRK', variant: '', uom: 'PCS', unit_price: '203.390000', ordered_quantity: '5.000000', grn_quantity: '3.000000', grn_cost: '610.170000' },
                    { id: 2, description: 'Coolant', item_code: 'COL', variant: '', uom: 'PCS', unit_price: '100.000000', ordered_quantity: '2.000000', grn_quantity: '2.000000', grn_cost: '200.000000' },
                ],
            }],
            summary: {
                total_orders: 1,
                currencies: [{ currency: 'LKR', po_amount: '1500.000000', grn_cost: '810.170000', ordered_quantity: '7.000000', grn_quantity: '5.000000', uom: 'PCS' }],
                suppliers: [{ supplier_id: 4, supplier: 'R F Marketing Services', currency: 'LKR', order_count: 1, po_amount: '1500.000000', grn_cost: '810.170000', ordered_quantity: '7.000000', grn_quantity: '5.000000', uom: 'PCS' }],
            },
            meta: { current_page: 1, from: 1, last_page: 1, per_page: 25, to: 1, total: 1 },
        });
    });

    it('shows the complete PO within its supplier and filters by PO or item', async () => {
        const user = userEvent.setup();
        render(<TestRouter><PurchaseOrderDetailReportPage /></TestRouter>);

        expect(await screen.findByRole('heading', { name: 'R F Marketing Services' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'PO-001' })).toHaveAttribute('href', '/purchase/orders/10');
        expect(screen.getByText('Nimali Perera')).toBeInTheDocument();
        expect(screen.getByText('Brake oil')).toBeInTheDocument();
        expect(screen.getByText('Coolant')).toBeInTheDocument();
        expect(screen.getByText('PO-001 total')).toBeInTheDocument();
        expect(screen.getByText('Supplier total for all 1 matching POs')).toBeInTheDocument();
        expect(screen.getAllByText(/810\.17/).length).toBeGreaterThan(0);

        await user.type(screen.getByLabelText('PO, supplier or item'), 'Brake');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));
        await waitFor(() => expect(apiMocks.runPurchaseOrderDetailReport).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: 'Brake', page: 1 }),
            expect.any(AbortSignal),
        ));
    });

    it('labels an approved PO when its approver was not recorded', async () => {
        const report = await apiMocks.runPurchaseOrderDetailReport();
        apiMocks.runPurchaseOrderDetailReport.mockResolvedValue({
            ...report,
            data: [{ ...report.data[0], status_action: { label: 'Approved by', actor: null } }],
        });

        render(<TestRouter><PurchaseOrderDetailReportPage /></TestRouter>);

        expect(await screen.findByText('Not recorded')).toBeInTheDocument();
    });

    it('shows the cancellation actor for a cancelled PO', async () => {
        const report = await apiMocks.runPurchaseOrderDetailReport();
        apiMocks.runPurchaseOrderDetailReport.mockResolvedValue({
            ...report,
            data: [{ ...report.data[0], status: 'cancelled', status_action: { label: 'Cancelled by', actor: { name: 'Kamal Silva' } } }],
        });

        render(<TestRouter><PurchaseOrderDetailReportPage /></TestRouter>);

        expect(await screen.findByText('Kamal Silva')).toBeInTheDocument();
        expect(screen.getByText('Cancelled by', { exact: false })).toBeInTheDocument();
        expect(screen.queryByText('Approved by', { exact: false })).not.toBeInTheDocument();
    });
});
