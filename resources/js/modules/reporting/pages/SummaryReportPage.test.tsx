import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import SummaryReportPage from './SummaryReportPage';

const apiMocks = vi.hoisted(() => ({
    runSummaryReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);

describe('SummaryReportPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.runSummaryReport.mockResolvedValue({
            period: {
                date_from: '2026-07-01',
                date_to: '2026-07-25',
            },
            currency_code: 'LKR',
            documents: {
                sales: documentMetrics('853253.31', 84),
                purchases: documentMetrics('615138.62', 13),
                sales_returns: documentMetrics('0', 0),
                purchase_returns: {
                    document_count: 2,
                    subtotal: '1000',
                    adjustment_total: '50',
                    grand_total: '1050',
                },
            },
            payments: {
                received: {
                    amount: '725395.71',
                    transaction_count: 81,
                    methods: [
                        {
                            type: 'cash',
                            name: 'Cash',
                            transaction_count: 40,
                            amount: '376270',
                        },
                    ],
                },
                sent: {
                    amount: '0',
                    transaction_count: 0,
                    methods: [],
                },
            },
            performance: {
                total_income: '853253.31',
                cost_of_sales: '305432.71',
                other_expenses: '0',
                total_expenses: '305432.71',
                net_profit: '547820.60',
            },
            capabilities: {
                sales_returns: { available: true, source: 'Finalized outbound credit notes' },
                purchase_returns: { available: true, source: 'Posted purchase returns' },
                payroll: {
                    available: false,
                    source: null,
                    message: 'Payroll is not available because no payroll transaction or payroll accounting category exists yet.',
                },
            },
        });
    });

    it('presents the period summary and refreshes an edited date range', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter>
                <SummaryReportPage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Summary Reports' })).toBeInTheDocument();
        expect(screen.getAllByText(/547,820\.60/)).not.toHaveLength(0);
        expect(screen.getByRole('heading', { name: 'Sales' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Payments received' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Not connected yet' })).toBeInTheDocument();

        const from = screen.getByLabelText('From');
        const to = screen.getByLabelText('To');
        await user.clear(from);
        await user.type(from, '2026-06-01');
        await user.clear(to);
        await user.type(to, '2026-06-30');
        await user.click(screen.getByRole('button', { name: 'Refresh report' }));

        await waitFor(() => {
            expect(apiMocks.runSummaryReport).toHaveBeenLastCalledWith(
                { date_from: '2026-06-01', date_to: '2026-06-30' },
                expect.any(AbortSignal),
            );
        });
    });
});

function documentMetrics(grandTotal: string, documentCount: number) {
    return {
        document_count: documentCount,
        subtotal: grandTotal,
        discount_total: '0',
        tax_total: '0',
        charge_total: '0',
        grand_total: grandTotal,
        paid_total: '0',
    };
}
