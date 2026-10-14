import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createSaleReturn, getSale, type SaleDocument } from '../sellingApi';
import SellingWorkspacePage from './SellingWorkspacePage';

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: ['super admin'], permissions: [], permissionsLoaded: true }),
}));
vi.mock('../sellingApi', async importOriginal => ({
    ...await importOriginal<typeof import('../sellingApi')>(),
    getSale: vi.fn(),
    createSaleReturn: vi.fn(),
}));

function sale(id: number): SaleDocument {
    return {
        id, row_version: 1, sale_number: `SALE-${id}`, sale_date: '2026-10-11',
        due_date: null, status: 'posted', customer: null, warehouse: null,
        warehouse_location: null, invoice: null, returns: [],
        lines: [{
            id: id * 10, line_number: 1, description: 'Oil', quantity: '2.000000',
            base_quantity: '2.000000', unit_price: '10.000000', line_total: '20.000000',
            item: { id: 1, code: 'OIL', name: 'Oil' },
            uom: { id: 1, code: 'EA', name: 'Each' },
        }],
    };
}

beforeEach(() => vi.clearAllMocks());

describe('Selling route state ownership', () => {
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
