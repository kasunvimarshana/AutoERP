import { isPositiveDecimal, compareDecimalStrings } from '@/shared/utils/decimal';
import { humanize } from '@/shared/utils/object';
import type { Invoice } from '../invoiceTypes';

type SettlementFacts = Pick<Invoice, 'status' | 'paid_total' | 'credit_total' | 'balance_due'>;

const green = 'bg-emerald-50 text-emerald-800 border-emerald-200/80';
const blue = 'bg-blue-50 text-blue-800 border-blue-200/80';
const amber = 'bg-amber-50 text-amber-800 border-amber-200/80';
const slate = 'bg-slate-50 text-slate-700 border-slate-200/80';
const red = 'bg-rose-50 text-rose-800 border-rose-200/80';

export function InvoiceStatusBadge({ invoice, status = invoice.status }: { invoice: SettlementFacts; status?: string | null }) {
    const display = invoiceStatusDisplay(invoice, status);

    return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium shadow-2xs border ${display.className}`}>{display.label}</span>;
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
