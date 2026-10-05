import { useMemo, useState } from 'react';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import type { Invoice } from '@/modules/invoice/invoiceTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { compareDecimalStrings, isPositiveDecimal } from '@/shared/utils/decimal';
import { allocatePayment, type Payment } from '../paymentApi';

const PAGE_SIZE = 50;
const ALLOCATION_METHOD = 'specific_invoice';

export function PaymentAllocationPanel({
    payment,
    enabled,
    onChanged,
}: {
    payment: Payment;
    enabled: boolean;
    onChanged: () => Promise<void>;
}) {
    const [search, setSearch] = useState('');
    const [invoiceId, setInvoiceId] = useState('');
    const [amount, setAmount] = useState('0.000000');
    const [allocationDate, setAllocationDate] = useState(businessDateInputValue());
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const invoiceDirection = payment.direction === 'inbound' ? 'outbound' : 'inbound';
    const partyId = payment.party?.id ?? null;
    const currencyId = payment.currency?.id ?? null;

    const invoices = useApi(
        (signal) => listInvoices({
            direction: invoiceDirection,
            party_id: partyId,
            currency_id: currencyId,
            settlement_eligible: true,
            search: search.trim() || undefined,
            per_page: PAGE_SIZE,
        }, signal),
        [invoiceDirection, partyId, currencyId, search],
        enabled && partyId !== null && currencyId !== null,
    );
    const rows = invoices.data?.data ?? [];
    const selected = useMemo(
        () => rows.find((invoice) => String(invoice.id) === invoiceId) ?? null,
        [invoiceId, rows],
    );
    const valid = enabled
        && selected !== null
        && isPositiveDecimal(amount)
        && compareDecimalStrings(amount, payment.unapplied_amount ?? '0') <= 0
        && compareDecimalStrings(amount, selected.balance_due ?? '0') <= 0;

    function selectInvoice(value: string) {
        setInvoiceId(value);
        const invoice = rows.find((row) => String(row.id) === value);
        if (!invoice) {
            setAmount('0.000000');
            return;
        }
        const unapplied = payment.unapplied_amount ?? '0.000000';
        const balance = invoice.balance_due ?? '0.000000';
        setAmount(compareDecimalStrings(unapplied, balance) <= 0 ? unapplied : balance);
    }

    async function apply() {
        if (!valid || !selected || busy) return;
        setBusy(true);
        setError(null);
        try {
            await allocatePayment(payment.id, payment.row_version, [{
                invoice_id: selected.id,
                allocated_amount: amount,
                allocation_date: allocationDate,
                allocation_method: ALLOCATION_METHOD,
            }]);
            setInvoiceId('');
            setAmount('0.000000');
            await onChanged();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (!enabled) return null;

    return (
        <div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 className="font-semibold text-slate-900">Apply payment</h3>
            <p className="mt-1 text-sm text-slate-600">
                Apply the unapplied balance to an open invoice for the same party and currency.
            </p>
            <ErrorAlert error={error ?? invoices.error} inline />
            <div className="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_180px_180px_auto] lg:items-end">
                <Input label="Find invoice" type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Invoice number" />
                <Select
                    label="Invoice"
                    value={invoiceId}
                    onChange={(event) => selectInvoice(event.target.value)}
                    options={[
                        { value: '', label: invoices.loading ? 'Loading invoices…' : 'Select invoice' },
                        ...rows.map((invoice: Invoice) => ({
                            value: String(invoice.id),
                            label: `${invoice.invoice_number ?? `Invoice ${invoice.id}`} · ${invoice.currency?.code ?? ''} ${invoice.balance_due ?? '0'}`,
                        })),
                    ]}
                />
                <DecimalInput label="Amount" value={amount} onChange={(event) => setAmount(event.target.value)} />
                <Input label="Allocation date" type="date" value={allocationDate} onChange={(event) => setAllocationDate(event.target.value)} />
                <Button disabled={!valid} loading={busy} onClick={() => void apply()}>Apply</Button>
            </div>
            <p className="mt-3 text-xs text-slate-500">
                Available: <MoneyDisplay value={payment.unapplied_amount} currency={payment.currency?.code ?? undefined} />
            </p>
        </div>
    );
}
