import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PaymentMethodWiseSummaryReportPage from './PaymentMethodWiseSummaryReportPage';

const apiMocks = vi.hoisted(() => ({
    runPaymentMethodWiseSummaryReport: vi.fn(),
    loadPaymentMethodWiseDocuments: vi.fn(),
}));
vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({ ExportActions: () => <div>Export actions</div> }));

describe('PaymentMethodWiseSummaryReportPage', () => {
    it('expands a daily total into linked payment and invoice documents', async () => {
        const user = userEvent.setup();
        apiMocks.runPaymentMethodWiseSummaryReport.mockResolvedValue({
            organization_unit: 'Main',
            methods: [{ key: 'CASH', name: 'Cash' }],
            days: [{ date: '2026-10-05', currency: 'LKR', methods: { CASH: '40.000000' }, receipts: '40.000000', credit_sales: '100.000000' }],
            totals: [{ currency: 'LKR', methods: { CASH: '40.000000' }, receipts: '40.000000', credit_sales: '100.000000' }],
        });
        apiMocks.loadPaymentMethodWiseDocuments.mockResolvedValue([{
            date: '2026-10-05', currency: 'LKR', methods: { CASH: '40.000000' }, receipts: '40.000000', credit_sales: '100.000000',
            documents: [
                { type: 'credit_sale', invoice: { id: 11, number: 'SALES-0011' }, payment: null, amount: '100.000000', methods: {} },
                { type: 'receipt', invoice: { id: 11, number: 'SALES-0011' }, payment: { id: 22, number: 'PAY-0022' }, amount: '40.000000', methods: { CASH: '40.000000' } },
            ],
        }]);

        render(<TestRouter><PaymentMethodWiseSummaryReportPage /></TestRouter>);

        expect(await screen.findByRole('button', { name: /2026-10-05/ })).toBeInTheDocument();
        expect(screen.queryByRole('columnheader', { name: 'Currency' })).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: /2026-10-05/ }));

        const payment = await screen.findByRole('link', { name: 'PAY-0022' });
        expect(payment).toHaveAttribute('href', '/payments/22');
        expect(screen.getAllByRole('link', { name: 'SALES-0011' })).toHaveLength(2);
        expect(within(payment.closest('tr')!).getByText(/Cash:/)).toBeInTheDocument();
        expect(apiMocks.loadPaymentMethodWiseDocuments).toHaveBeenCalledWith(
            expect.objectContaining({ source: 'all' }), '2026-10-05', expect.any(AbortSignal),
        );
    });
});
