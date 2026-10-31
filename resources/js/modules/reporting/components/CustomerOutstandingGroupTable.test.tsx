import { useState } from 'react';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import type { CustomerOutstandingInvoicesResult, CustomerOutstandingRow } from '@/modules/finance/financeTypes';
import { CustomerOutstandingGroupTable, type CustomerOutstandingSelection } from './CustomerOutstandingGroupTable';

const customers: CustomerOutstandingRow[] = [
    {
        location: { id: 1, name: 'Main' },
        customer: { id: 10, name: 'Alpha Motors', code: 'ALPHA', customer_number: 'CUS-001' },
        currency: { code: 'LKR' },
        invoice_count: 1,
        current: '0', days_1_30: '1200', days_31_60: '0', days_61_90: '0', over_90: '0', outstanding_total: '1200',
    },
    {
        location: { id: 1, name: 'Main' },
        customer: { id: 11, name: 'Beta Motors', code: 'BETA', customer_number: 'CUS-002' },
        currency: { code: 'LKR' },
        invoice_count: 1,
        current: '500', days_1_30: '0', days_31_60: '0', days_61_90: '0', over_90: '0', outstanding_total: '500',
    },
];

const invoices: CustomerOutstandingInvoicesResult = {
    as_of_date: '2026-10-28',
    customer: customers[0].customer,
    currency: { code: 'LKR' },
    data: [{
        invoice: { id: 42, number: 'SERVICE-042', date: '2026-10-01', due_date: '2026-10-01', service_job_number: 'JOB-042' },
        currency: { code: 'LKR' },
        invoice_total: '1200', paid_amount: '0', credit_allocated_amount: '0', outstanding_amount: '1200',
        invoice_age_days: 27, days_overdue: 27,
    }],
    meta: { current_page: 1, from: 1, last_page: 1, per_page: 10, to: 1, total: 1 },
};

function TestTable() {
    const [selected, setSelected] = useState<CustomerOutstandingSelection | null>(null);

    return <CustomerOutstandingGroupTable
        rows={customers}
        selected={selected}
        invoiceResult={invoices}
        invoiceLoading={false}
        invoiceError={null}
        onSelect={(row) => setSelected((current) => current?.customerId === row.customer.id ? null : {
            customerId: row.customer.id,
            currencyCode: row.currency.code,
            locationId: row.location.id,
        })}
        onInvoicePageChange={() => {}}
    />;
}

describe('CustomerOutstandingGroupTable', () => {
    it('expands invoices directly below the customer and keeps the R8 columns concise', async () => {
        const user = userEvent.setup();
        const { container } = render(<TestRouter><TestTable /></TestRouter>);
        const customerTable = container.querySelector('table')!;

        expect(within(customerTable).queryByRole('columnheader', { name: 'Code' })).not.toBeInTheDocument();
        expect(within(customerTable).queryByRole('columnheader', { name: 'Currency' })).not.toBeInTheDocument();
        expect(customerTable.tBodies[0].children).toHaveLength(2);

        await user.click(within(customerTable).getByRole('button', { name: /Alpha Motors/ }));

        expect(customerTable.tBodies[0].children).toHaveLength(3);
        expect(customerTable.tBodies[0].children[1]).toHaveTextContent('SERVICE-042');
        expect(customerTable.tBodies[0].children[1]).toHaveTextContent('Service job: JOB-042');
        expect(customerTable.tBodies[0].children[2]).toHaveTextContent('Beta Motors');
        expect(screen.getAllByRole('link', { name: 'SERVICE-042' })[0]).toHaveAttribute('href', '/invoices/42');

        await user.click(within(customerTable).getByRole('button', { name: /Beta Motors/ }));
        expect(customerTable.tBodies[0].children).toHaveLength(3);
        expect(customerTable.tBodies[0].children[1]).toHaveTextContent('Beta Motors');
    });
});
