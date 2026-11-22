/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { InvoiceStatusBadge } from './InvoiceStatusBadge';

describe('InvoiceStatusBadge', () => {
    it('reserves green for an invoice fully settled by payment', () => {
        render(<>
            <InvoiceStatusBadge invoice={{ status: 'posted', paid_total: '0.000000', credit_total: '0.000000', balance_due: '1200.000000' }} />
            <InvoiceStatusBadge invoice={{ status: 'partially_paid', paid_total: '400.000000', credit_total: '0.000000', balance_due: '800.000000' }} />
            <InvoiceStatusBadge invoice={{ status: 'paid', paid_total: '1200.000000', credit_total: '0.000000', balance_due: '0.000000' }} />
            <InvoiceStatusBadge invoice={{ status: 'paid', paid_total: '200.000000', credit_total: '1000.000000', balance_due: '0.000000' }} />
        </>);

        expect(screen.getByText('Posted')).toHaveClass('bg-amber-50', 'text-amber-800');
        expect(screen.getByText('Partially Paid')).toHaveClass('bg-blue-50', 'text-blue-800');
        expect(screen.getByText('Paid')).toHaveClass('bg-emerald-50', 'text-emerald-800');
        expect(screen.getByText('Settled with credit')).toHaveClass('bg-blue-50', 'text-blue-800');
    });

    it('keeps the shared posted badge for other modules unchanged', () => {
        render(<StatusBadge status="posted" />);

        expect(screen.getByText('Posted')).toHaveClass('bg-emerald-100');
    });
});
