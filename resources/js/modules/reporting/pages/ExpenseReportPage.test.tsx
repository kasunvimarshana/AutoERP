import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import ExpenseReportPage from './ExpenseReportPage';

const apiMocks = vi.hoisted(() => ({
    runExpenseReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: ({ reportKey }: { reportKey: string }) => <div data-testid="export-actions">{reportKey}</div>,
}));

describe('ExpenseReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runExpenseReport.mockResolvedValue(expenseReportResult());
    });

    it('explains expense composition and applies guided filters', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/reports/expenses']}>
                <ExpenseReportPage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Overall expense report' })).toBeInTheDocument();
        expect(screen.getByText('Net expense · Profit and Loss')).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Paid expense types' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Consumables by item' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'How paid expenses were paid' })).toBeInTheDocument();
        expect(screen.getByTestId('export-actions')).toHaveTextContent('expenses');

        await user.selectOptions(screen.getByLabelText('Source'), 'paid');
        await user.click(screen.getByText('More filters'));
        await user.type(screen.getByLabelText('Search'), 'office');
        await user.selectOptions(screen.getByLabelText('Expense type (paid only)'), '1');
        await user.selectOptions(screen.getByLabelText('Activity'), 'posted');
        await user.click(screen.getByRole('button', { name: 'Apply filters' }));

        await waitFor(() => {
            expect(apiMocks.runExpenseReport).toHaveBeenLastCalledWith(
                expect.objectContaining({
                    page: 1,
                    per_page: 25,
                    search: 'office',
                    source: 'paid',
                    expense_type_id: 1,
                    event_type: 'posted',
                }),
                expect.any(AbortSignal),
            );
        });
    });
});

function expenseReportResult() {
    return {
        report: {
            key: 'expenses',
            title: 'Expense Report',
            group: 'Finance',
            description: 'Operating expense activity.',
            columns: [
                { key: 'event_date', label: 'Date', sortable: true, format: 'date' },
                { key: 'expense', label: 'Expense', sortable: true, format: 'text' },
                { key: 'net_amount', label: 'Net amount', sortable: true, format: 'money' },
            ],
            filters: [],
            supports_date_range: true,
            default_sort: 'event_date',
            default_direction: 'desc',
        },
        data: [{ id: 'posted-1', event_date: '2026-09-10', expense: 'Office rent', net_amount: '50000' }],
        summary: {
            event_count: 4,
            posted_count: 3,
            reversal_count: 1,
            posted_amount: '65000',
            reversed_amount: '5000',
            net_amount: '60000',
        },
        by_expense_type: [
            { id: 1, code: 'RENT', name: 'Rent', transaction_count: 2, posted_amount: '50000', reversed_amount: '0', net_amount: '50000' },
            { id: 2, code: 'UTIL', name: 'Utilities', transaction_count: 1, posted_amount: '15000', reversed_amount: '5000', net_amount: '10000' },
        ],
        by_payment_method: [
            { id: 1, code: 'BANK', name: 'Bank transfer', transaction_count: 3, posted_amount: '65000', reversed_amount: '5000', net_amount: '60000' },
        ],
        by_source: [
            { source: 'paid', net_amount: '50000' },
            { source: 'consumable', net_amount: '10000' },
        ],
        by_consumable_item: [{ id: 3, code: 'SHAMPOO', name: 'Shampoo', quantity: '2.000000', net_amount: '10000' }],
        by_other_account: [],
        trend: [{ date: '2026-09-10', posted_amount: '65000', reversed_amount: '5000', net_amount: '60000' }],
        filter_options: {
            expense_types: [{ id: 1, name: 'Rent' }],
            payment_methods: [{ id: 1, name: 'Bank transfer' }],
            items: [{ id: 3, name: 'Shampoo' }],
        },
        currency_code: 'LKR',
        period: { date_from: '2026-09-01', date_to: '2026-09-23' },
        basis: 'Posting events use the expense date; reversal events use the reversal date.',
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 25, to: 1, total: 1 },
    };
}
