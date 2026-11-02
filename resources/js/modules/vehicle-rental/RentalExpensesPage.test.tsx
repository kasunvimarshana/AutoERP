import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RentalExpensesPage from './RentalExpensesPage';
import {
    ExpenseStatus,
    ExpenseType,
    type RentalExpense,
} from './rentalExpenseApi';
import * as rentalExpenseApi from './rentalExpenseApi';

const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./rentalExpenseApi', async (importOriginal) => {
    const actual = await importOriginal<typeof rentalExpenseApi>();
    return {
        ...actual,
        listRentalExpenses: vi.fn(),
        transitionRentalExpense: vi.fn(),
    };
});

const mockExpense: RentalExpense = {
    id: 1,
    row_version: 1,
    expense_number: 'EXP-202611-0001',
    vehicle: { id: 12, label: 'WP-PX-2020 (Toyota KDH)', make: 'Toyota', model: 'KDH' },
    expense_type: ExpenseType.Fuel,
    expense_type_label: 'Fuel',
    expense_date: '2026-11-02',
    currency: { id: 1, code: 'LKR' },
    net_amount: '15000.00',
    tax_amount: '0.00',
    gross_amount: '15000.00',
    supplier: { id: 5, name: 'Lanka IOC Fuel Station' },
    reference_number: 'PET-88992',
    description: 'Fuel refill during delivery',
    status: ExpenseStatus.Draft,
    allocations: [],
    created_at: '2026-11-02 09:00:00',
};

beforeEach(() => {
    vi.clearAllMocks();
    session.permissions = ['vehicle-rental.expenses.view'];
    vi.mocked(rentalExpenseApi.listRentalExpenses).mockResolvedValue({
        data: [mockExpense],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
    });
});

describe('RentalExpensesPage', () => {
    it('renders rental expense list in read-only mode for view-only users', async () => {
        render(<RentalExpensesPage />);
        expect(await screen.findByText('EXP-202611-0001')).toBeInTheDocument();
        expect(screen.getByText('Fuel')).toBeInTheDocument();
        expect(screen.getByText('WP-PX-2020 (Toyota KDH)')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Record Expense' })).not.toBeInTheDocument();
    });

    it('allows manager to submit a draft expense for approval', async () => {
        session.permissions = ['vehicle-rental.expenses.view', 'vehicle-rental.expenses.manage'];
        vi.mocked(rentalExpenseApi.transitionRentalExpense).mockResolvedValue({
            ...mockExpense,
            status: ExpenseStatus.Submitted,
            row_version: 2,
        });

        render(<RentalExpensesPage />);
        expect(await screen.findByRole('button', { name: 'Record Expense' })).toBeInTheDocument();

        // Click Review
        const reviewBtn = screen.getByRole('button', { name: 'Review' });
        fireEvent.click(reviewBtn);

        expect(await screen.findByRole('region', { name: 'Expense review' })).toBeInTheDocument();
        const submitBtn = screen.getByRole('button', { name: 'Submit for Approval' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(rentalExpenseApi.transitionRentalExpense).toHaveBeenCalledWith(
                1,
                1,
                ExpenseStatus.Submitted,
                undefined
            );
        });
    });

    it('allows manager to cancel/reverse an expense', async () => {
        session.permissions = ['vehicle-rental.expenses.view', 'vehicle-rental.expenses.manage'];
        vi.mocked(rentalExpenseApi.transitionRentalExpense).mockResolvedValue({
            ...mockExpense,
            status: ExpenseStatus.Reversed,
            row_version: 2,
        });

        render(<RentalExpensesPage />);
        const reviewBtn = await screen.findByRole('button', { name: 'Review' });
        fireEvent.click(reviewBtn);

        const reverseBtn = await screen.findByRole('button', { name: 'Cancel / Reverse' });
        fireEvent.click(reverseBtn);

        await waitFor(() => {
            expect(rentalExpenseApi.transitionRentalExpense).toHaveBeenCalledWith(
                1,
                1,
                ExpenseStatus.Reversed,
                undefined
            );
        });
    });
});
