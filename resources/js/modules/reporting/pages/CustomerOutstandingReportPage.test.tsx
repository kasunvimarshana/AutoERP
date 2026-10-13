import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { getCustomerOutstanding } from '@/modules/finance/financeApi';
import type { CustomerOutstandingReportResult } from '@/modules/finance/financeTypes';
import CustomerOutstandingReportPage from './CustomerOutstandingReportPage';

vi.mock('@/modules/finance/financeApi', () => ({
    getCustomerOutstanding: vi.fn(), getCustomerOutstandingInvoices: vi.fn(), downloadCustomerOutstandingCsv: vi.fn(),
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({ CustomerLookupSelect: () => null }));

function result(name: string): CustomerOutstandingReportResult {
    return {
        as_of_date: '2026-06-30',
        summary: { customer_count: 1, overdue_invoice_count: 0, totals_by_currency: [] },
        data: [{
            customer: { id: 1, name, code: 'CUSTOMER', customer_number: 'CUS-001' },
            currency: { code: 'LKR' }, invoice_count: 1, current: '100', days_1_30: '0',
            days_31_60: '0', days_61_90: '0', over_90: '0', outstanding_total: '100',
        }],
        meta: { current_page: 1, from: 1, to: 1, last_page: 1, per_page: 25, total: 1 },
    };
}

describe('Customer outstanding request ownership', () => {
    it('keeps the current filter result when a previous request finishes late', async () => {
        let completePrevious!: (value: CustomerOutstandingReportResult) => void;
        vi.mocked(getCustomerOutstanding)
            .mockReturnValueOnce(new Promise(resolve => { completePrevious = resolve; }))
            .mockResolvedValueOnce(result('Current customer'));
        render(<MemoryRouter><CustomerOutstandingReportPage /></MemoryRouter>);
        await waitFor(() => expect(getCustomerOutstanding).toHaveBeenCalledOnce());
        fireEvent.change(screen.getByLabelText('Invoice date to'), { target: { value: '2026-06-30' } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        await screen.findAllByRole('button', { name: /Current customer/ });
        await act(async () => completePrevious(result('Previous customer')));

        expect(screen.getAllByRole('button', { name: /Current customer/ }).length).toBeGreaterThan(0);
        expect(screen.queryAllByRole('button', { name: /Previous customer/ })).toHaveLength(0);
    });
});
