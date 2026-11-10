import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { lookupApi } from '@/shared/api/lookupApi';
import { createSale, createSaleReturn, getDirectCustomer, getSale, getSellingWarehouseSource, searchSellingCustomers, type SaleDocument } from '../sellingApi';
import SellingWorkspacePage from './SellingWorkspacePage';

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: ['super admin'], permissions: [], permissionsLoaded: true }),
}));
vi.mock('../sellingApi', async importOriginal => ({
    ...await importOriginal<typeof import('../sellingApi')>(),
    getSale: vi.fn(),
    createSaleReturn: vi.fn(),
    createSale: vi.fn(),
    getDirectCustomer: vi.fn(),
    getSellingWarehouseSource: vi.fn(),
    searchSellingCustomers: vi.fn(),
}));

function sale(id: number): SaleDocument {
    return {
        id, row_version: 1, sale_number: `SALE-${id}`, sale_date: '2026-10-11',
        due_date: null, status: 'posted', bill_discount_type: 'fixed', bill_discount_value: '0.000000', bill_discount_amount: '0.000000', customer: null, warehouse: null,
        warehouse_location: null, invoice: null, returns: [],
        lines: [{
            id: id * 10, line_number: 1, description: 'Oil', quantity: '2.000000',
            base_quantity: '2.000000', unit_price: '10.000000', discount_type: 'fixed', discount_value: '0.000000', discount_amount: '0.000000', bill_discount_allocated: '0.000000', line_total: '20.000000',
            item: { id: 1, code: 'OIL', name: 'Oil' },
            uom: { id: 1, code: 'EA', name: 'Each' },
        }],
    };
}

beforeEach(() => vi.clearAllMocks());
afterEach(() => vi.restoreAllMocks());

describe('Selling route state ownership', () => {
    it('sends line and bill discounts with selected stocked items', async () => {
        vi.mocked(getDirectCustomer).mockResolvedValue({ id: 10, name: 'Direct Customer' } as Awaited<ReturnType<typeof getDirectCustomer>>);
        vi.mocked(getSellingWarehouseSource).mockResolvedValue({ warehouse: { id: 1, name: 'Main Warehouse' }, location: { id: 2, name: 'Sales Floor' } });
        vi.spyOn(lookupApi, 'sellingStockableItems').mockResolvedValue({
            data: [{ id: 3, code: 'OIL', name: 'Oil filter', base_uom: { id: 4, code: 'PCS', name: 'Pieces' }, available_stock_quantity: '5.000000', resolved_sales_unit_price: '100.000000', tenant_base_currency: { id: 5, code: 'LKR', name: 'Rupee', symbol: 'LKR' } }],
        });
        vi.mocked(createSale).mockResolvedValue(sale(1));
        vi.mocked(getSale).mockResolvedValue(sale(1));

        const router = createMemoryRouter([
            { path: '/selling/create', element: <SellingWorkspacePage /> },
            { path: '/selling/sales/:id', element: <SellingWorkspacePage /> },
        ], { initialEntries: ['/selling/create'] });
        render(<RouterProvider router={router} />);

        await waitFor(() => expect(screen.getByRole('combobox', { name: 'Location' })).toHaveValue('Sales Floor'));
        await waitFor(() => expect(screen.getByRole('combobox', { name: 'Item' })).toBeEnabled());
        const itemSearch = screen.getByRole('combobox', { name: 'Item' });
        fireEvent.change(itemSearch, { target: { value: 'Oil' } });
        const itemOption = await screen.findByRole('option', { name: /Oil filter/ }, { timeout: 5000 });
        expect(itemOption).toHaveTextContent('Available: 5.000000 PCS');
        expect(itemOption).toHaveTextContent('Sale price: LKR 100.000000');
        fireEvent.click(itemOption);
        fireEvent.change(screen.getAllByRole('textbox', { name: 'Discount for Oil filter' }).at(-1)!, { target: { value: '10' } });
        fireEvent.change(screen.getByRole('textbox', { name: 'Total bill discount' }), { target: { value: '5' } });
        fireEvent.click(screen.getByRole('button', { name: 'Post sale and invoice' }));

        await waitFor(() => expect(createSale).toHaveBeenCalledWith(expect.objectContaining({
            bill_discount_type: 'fixed',
            bill_discount_value: '5',
            lines: [expect.objectContaining({ item_id: 3, quantity: '1', discount_type: 'fixed', discount_value: '10' })],
        })));
    });

    it('loads Direct Customer by default and allows another customer to be selected', async () => {
        vi.mocked(getDirectCustomer).mockResolvedValue({ id: 10, name: 'Direct Customer' } as Awaited<ReturnType<typeof getDirectCustomer>>);
        vi.mocked(getSellingWarehouseSource).mockResolvedValue({ warehouse: null, location: null });
        vi.mocked(searchSellingCustomers).mockResolvedValue({
            data: [{ id: 20, name: 'Alice Customer' } as Awaited<ReturnType<typeof getDirectCustomer>>],
        });

        const router = createMemoryRouter([
            { path: '/selling/create', element: <SellingWorkspacePage /> },
        ], { initialEntries: ['/selling/create'] });
        render(<RouterProvider router={router} />);

        const customer = await screen.findByRole('combobox', { name: /Customer/ });
        await waitFor(() => expect(customer).toHaveValue('Direct Customer'));
        fireEvent.change(customer, { target: { value: 'Alice' } });
        fireEvent.click(await screen.findByRole('option', { name: /Alice Customer/ }));

        expect(customer).toHaveValue('Alice Customer');
    });

    it('keeps a late return response on its original sale after navigation', async () => {
        vi.mocked(getSale).mockImplementation(async id => sale(id));
        let completeReturn!: (value: SaleDocument) => void;
        vi.mocked(createSaleReturn).mockReturnValue(new Promise(resolve => { completeReturn = resolve; }));
        const router = createMemoryRouter([
            { path: '/selling/sales/:id', element: <SellingWorkspacePage /> },
        ], { initialEntries: ['/selling/sales/1'] });
        render(<RouterProvider router={router} />);
        await screen.findByRole('heading', { name: 'Sale SALE-1' });
        fireEvent.change(screen.getByLabelText(/Oil · remaining/), { target: { value: '1' } });
        fireEvent.change(screen.getByLabelText('Return reason'), { target: { value: 'Damaged bottle' } });
        fireEvent.click(screen.getByRole('button', { name: 'Post return and credit note' }));
        await waitFor(() => expect(createSaleReturn).toHaveBeenCalledOnce());

        await act(async () => { await router.navigate('/selling/sales/2'); });
        await screen.findByRole('heading', { name: 'Sale SALE-2' });
        await act(async () => completeReturn({ ...sale(1), row_version: 2 }));

        expect(screen.getByRole('heading', { name: 'Sale SALE-2' })).toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Sale SALE-1' })).not.toBeInTheDocument();
        expect(screen.getByLabelText('Return reason')).toHaveValue('');
    });
});
