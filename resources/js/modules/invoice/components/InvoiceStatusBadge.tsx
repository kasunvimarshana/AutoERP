import { isPositiveDecimal, compareDecimalStrings } from '@/shared/utils/decimal';
import { humanize } from '@/shared/utils/object';
import type { Invoice } from '../invoiceTypes';

type SettlementFacts = Pick<Invoice, 'status' | 'paid_total' | 'credit_total' | 'balance_due'>;

const green = 'bg-emerald-100 text-emerald-700';
const blue = 'bg-blue-100 text-blue-700';
const amber = 'bg-amber-100 text-amber-800';
const slate = 'bg-slate-100 text-slate-700';
const red = 'bg-rose-100 text-rose-700';

export function InvoiceStatusBadge({ invoice, status = invoice.status }: { invoice: SettlementFacts; status?: string | null }) {
    const display = invoiceStatusDisplay(invoice, status);

    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${display.className}`}>{display.label}</span>;
}

function invoiceStatusDisplay(invoice: SettlementFacts, status?: string | null): { label: string; className: string } {
    const settled = invoice.balance_due !== undefined && compareDecimalStrings(invoice.balance_due, '0') === 0;

    if (status === 'paid') {
        if (settled && isPositiveDecimal(invoice.credit_total ?? '0')) {
            return { label: 'Settled with credit', className: blue };
        }
        if (settled && isPositiveDecimal(invoice.paid_total ?? '0') && compareDecimalStrings(invoice.credit_total ?? '0', '0') === 0) {
            return { label: 'Paid', className: green };
        }

        return { label: 'Paid', className: amber };
    }

    if (status === 'posted' || status === 'unpaid') return { label: humanize(status), className: amber };
    if (status === 'approved' || status === 'partially_paid' || status === 'partial') return { label: humanize(status), className: blue };
    if (status === 'draft') return { label: humanize(status), className: slate };
    if (status === 'cancelled' || status === 'void' || status === 'reversed') return { label: humanize(status), className: red };

    return { label: humanize(status), className: slate };
}
