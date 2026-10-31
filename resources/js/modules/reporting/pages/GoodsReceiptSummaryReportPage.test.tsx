import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import GoodsReceiptSummaryReportPage from './GoodsReceiptSummaryReportPage';

const apiMocks = vi.hoisted(() => ({ runGoodsReceiptSummaryReport: vi.fn() }));
vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({ ExportActions: () => <div>Export actions</div> }));
vi.mock('@/modules/purchase/components/PurchaseLookups', () => ({
    SupplierLookupSelect: () => <div>Supplier lookup</div>,
    WarehouseLookupSelect: () => <div>Warehouse lookup</div>,
}));

describe('GoodsReceiptSummaryReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runGoodsReceiptSummaryReport.mockResolvedValue({
            report: { title: 'Goods Receipt Note Summary Report' },
            data: [{
                id: 7,
                grn_number: 'GRN-0007',
                received_date: '2026-10-05',
                supplier: 'Semini Motors',
                purchase_order: { id: 3, number: 'PO-0003' },
                note: 'Supplier invoice 0299022',
                location_id: 1,
                location: 'Main',
                currency: 'LKR',
                accepted_quantity: '2.000000',
                uom: 'PCS',
                total_cost: '200.000000',
            }],
            summary: {
                total_receipts: 2,
                locations: [{ location_id: 1, location: 'Main', currency: 'LKR', receipt_count: 2, accepted_quantity: null, uom: null, total_cost: '220.000000' }],
                currencies: [{ currency: 'LKR', receipt_count: 2, accepted_quantity: null, uom: null, total_cost: '220.000000' }],
            },
            meta: { current_page: 1, from: 1, last_page: 2, per_page: 1, to: 1, total: 2 },
        });
    });

    it('shows linked GRNs and POs with notes and full filtered totals', async () => {
        const user = userEvent.setup();
        render(<TestRouter><GoodsReceiptSummaryReportPage /></TestRouter>);

        expect(await screen.findByRole('link', { name: 'GRN-0007' })).toHaveAttribute('href', '/purchase/goods-receipts/7');
        expect(screen.getByRole('link', { name: 'PO-0003' })).toHaveAttribute('href', '/purchase/orders/3');
        expect(screen.getByText('Supplier invoice 0299022')).toBeInTheDocument();
        expect(screen.getByText('Main total · 2 GRNs across all matching pages')).toBeInTheDocument();
        expect(screen.getAllByText('Mixed units')).toHaveLength(2);
        expect(screen.queryByText('Free qty')).not.toBeInTheDocument();

        await user.type(screen.getByLabelText('GRN, PO, supplier or note'), '0299022');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));
        await waitFor(() => expect(apiMocks.runGoodsReceiptSummaryReport).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: '0299022', page: 1 }),
            expect.any(AbortSignal),
        ));
    });
});
