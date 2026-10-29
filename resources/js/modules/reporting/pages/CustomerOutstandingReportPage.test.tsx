import { render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import CustomerOutstandingReportPage from './CustomerOutstandingReportPage';

const financeApi = vi.hoisted(() => ({
    getCustomerOutstanding: vi.fn(),
    getCustomerOutstandingLocations: vi.fn(),
    getCustomerOutstandingInvoices: vi.fn(),
    downloadCustomerOutstandingCsv: vi.fn(),
}));
vi.mock('@/modules/finance/financeApi', () => financeApi);
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({ CustomerLookupSelect: () => <div>Customer search</div> }));

afterEach(() => {
    configureBusinessTimeZone(null);
    vi.useRealTimers();
    vi.clearAllMocks();
});

describe('CustomerOutstandingReportPage', () => {
    it('sends the displayed business date and timezone together at the UTC date boundary', async () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        vi.setSystemTime(new Date('2026-10-28T23:34:48Z'));
        configureBusinessTimeZone('Asia/Colombo');
        financeApi.getCustomerOutstandingLocations.mockResolvedValue([]);
        financeApi.getCustomerOutstanding.mockResolvedValue({
            as_of_date: '2026-10-29',
            summary: { customer_count: 0, overdue_invoice_count: 0, totals_by_currency: [], totals_by_location: [] },
            data: [],
            meta: { current_page: 1, from: null, last_page: 1, per_page: 25, to: null, total: 0 },
        });

        render(<TestRouter><CustomerOutstandingReportPage /></TestRouter>);

        expect(screen.getByLabelText('As at date')).toHaveValue('2026-10-29');
        expect(screen.getByLabelText('As at date')).toHaveAttribute('max', '2026-10-29');
        await waitFor(() => expect(financeApi.getCustomerOutstanding).toHaveBeenCalledWith(
            expect.objectContaining({ as_of_date: '2026-10-29', timezone: 'Asia/Colombo' }),
            expect.any(AbortSignal),
        ));
    });
});
