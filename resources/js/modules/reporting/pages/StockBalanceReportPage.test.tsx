import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import StockBalanceReportPage from './StockBalanceReportPage';

const apiMocks = vi.hoisted(() => ({ runStockBalanceReport: vi.fn(), loadStockBalanceDetails: vi.fn() }));
vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({ ExportActions: () => <div>Export actions</div> }));

describe('StockBalanceReportPage', () => {
    it('shows the item and key stock quantities, then expands the batch breakdown', async () => {
        const user = userEvent.setup();
        apiMocks.runStockBalanceReport.mockResolvedValue({
            data: [{
                item_id: 4, item_variant_id: null, base_uom_id: null, warehouse_id: 2, location_id: 3,
                item_name: 'Brake Fluid DOT 3', item_code: '1138', variant_name: null, variant_code: null,
                warehouse: 'Main Warehouse', location: 'Main', uom: 'PCS',
                quantity_on_hand: 9, quantity_available: 5,
                quantity_damaged: '2.000000', quantity_expired: '1.000000', stock_level: 'low_stock',
            }],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
            generated_at: '2026-10-29T08:00:00Z',
        });
        apiMocks.loadStockBalanceDetails.mockResolvedValue([{
            batch_number: 'LOT-A', quantity_on_hand: '9', quantity_available: '5', quantity_reserved: '1',
            quantity_allocated: '0', quantity_returned: '0', quantity_in_transit: '0', quantity_damaged: '2',
            quantity_quarantine: '0', quantity_expired: '1', quantity_scrapped: '0',
        }]);

        render(<TestRouter><StockBalanceReportPage /></TestRouter>);

        expect(await screen.findByRole('button', { name: /Brake Fluid DOT 3/ })).toBeInTheDocument();
        expect(screen.getByRole('columnheader', { name: 'Current stock' })).toBeInTheDocument();
        expect(screen.getByRole('columnheader', { name: 'Available to sell' })).toBeInTheDocument();
        expect(screen.getByRole('columnheader', { name: 'Damaged' })).toBeInTheDocument();
        expect(screen.getByRole('columnheader', { name: 'Expired' })).toBeInTheDocument();
        expect(screen.getByText('Low stock')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: /Brake Fluid DOT 3/ }));
        expect(await screen.findByText('LOT-A')).toBeInTheDocument();
        expect(apiMocks.loadStockBalanceDetails).toHaveBeenCalledWith(expect.objectContaining({ item_id: 4, location_id: 3 }), expect.any(AbortSignal));
    });
});
