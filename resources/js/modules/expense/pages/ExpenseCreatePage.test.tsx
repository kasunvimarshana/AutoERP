import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ExpenseCreatePage from './ExpenseCreatePage';

const apiMocks = vi.hoisted(() => ({
    createExpense: vi.fn(),
    listExpensePaymentMethods: vi.fn(),
    listExpenseTypeOptions: vi.fn(),
}));

vi.mock('../expenseApi', () => apiMocks);

describe('ExpenseCreatePage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listExpenseTypeOptions.mockResolvedValue({
            data: [{
                id: 1,
                row_version: 1,
                code: 'RENT',
                name: 'Rent',
                is_active: true,
                sort_order: 1,
            }],
        });
        apiMocks.listExpensePaymentMethods.mockResolvedValue([{
            id: 2,
            code: 'BANK',
            name: 'Bank Transfer',
            type: 'bank_transfer',
            requires_reference: false,
            requires_instrument_details: false,
        }]);
        apiMocks.createExpense.mockResolvedValue({ id: 3 });
    });

    it('submits a positive decimal amount without incompatible native step validation', async () => {
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/expenses/create']}><ExpenseCreatePage /></TestRouter>);

        const amount = await screen.findByRole('spinbutton', { name: 'Amount' });
        expect(amount).toHaveAttribute('min', '0.000001');
        expect(amount).toHaveAttribute('step', '0.000001');
        expect(amount).toBeValid();

        const selects = screen.getAllByRole('combobox');
        await user.selectOptions(selects[0], '1');
        await user.selectOptions(selects[1], '2');
        await user.type(amount, '100.9');
        await user.click(screen.getByRole('button', { name: 'Post expense' }));

        await waitFor(() => expect(apiMocks.createExpense).toHaveBeenCalledWith(expect.objectContaining({
            amount: '100.9',
        })));
    });
});
