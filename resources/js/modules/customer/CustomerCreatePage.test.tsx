import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import CustomerCreatePage from './CustomerCreatePage';

const mocks = vi.hoisted(() => ({ createCustomer: vi.fn(), createCustomerWithRelations: vi.fn() }));

vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => false }));
vi.mock('./customerApi', () => ({
    createCustomer: mocks.createCustomer,
    createCustomerWithRelations: mocks.createCustomerWithRelations,
    generateCustomerCode: async () => 'CUS-NEW',
    searchCurrencies: async () => ({ data: [{ id: 1, code: 'LKR', name: 'Rupee' }] }),
}));

describe('Customer creation address', () => {
    it('saves the general address with the customer without enabling related-record entry', async () => {
        mocks.createCustomerWithRelations.mockResolvedValue({ id: 21 });
        const user = userEvent.setup();

        render(<TestRouter><CustomerCreatePage /></TestRouter>);
        await user.type(await screen.findByLabelText('Name'), 'Nimal');
        await user.type(screen.getByLabelText('Phone'), '0112345678');
        await user.type(screen.getByLabelText('Customer address'), '123 Main Street');
        await user.click(screen.getByRole('button', { name: 'Create customer' }));

        await waitFor(() => expect(mocks.createCustomerWithRelations).toHaveBeenCalledWith(expect.objectContaining({
            addresses: [{ address_type: 'general', address_line_1: '123 Main Street', is_primary: true, is_active: true }],
        })));
        expect(mocks.createCustomer).not.toHaveBeenCalled();
    });
});
