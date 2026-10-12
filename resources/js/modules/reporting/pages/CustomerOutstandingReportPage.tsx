import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { formatMoney } from '@/shared/utils/formatMoney';
import { getCustomerOutstanding, getCustomerOutstandingInvoices, downloadCustomerOutstandingCsv } from '@/modules/finance/financeApi';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import type {
    CustomerOutstandingInvoiceRow,
    CustomerOutstandingInvoicesResult,
    CustomerOutstandingParams,
    CustomerOutstandingReportResult,
    CustomerOutstandingRow,
    ReceivableAgingBucket,
} from '@/modules/finance/financeTypes';

const INITIAL_FILTERS: CustomerOutstandingParams = {
    as_of_date: localDateToday(),
    page: 1,
    per_page: 25,
};

const AGING_OPTIONS = [
    { value: 'current', label: 'Current' },
    { value: '1_30', label: '1–30 days overdue' },
    { value: '31_60', label: '31–60 days overdue' },
    { value: '61_90', label: '61–90 days overdue' },
    { value: '90_plus', label: '90+ days overdue' },
];

export default function CustomerOutstandingReportPage() {
    const [filters, setFilters] = useState<CustomerOutstandingParams>(INITIAL_FILTERS);
    const [draft, setDraft] = useState<CustomerOutstandingParams>(INITIAL_FILTERS);
    const [draftCustomer, setDraftCustomer] = useState<CustomerSummary | null>(null);
    const [result, setResult] = useState<CustomerOutstandingReportResult | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [exporting, setExporting] = useState(false);
    const [exportError, setExportError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<{ customerId: number; customerName: string; currencyCode: string } | null>(null);
    const [invoicePage, setInvoicePage] = useState(1);
    const [invoiceResult, setInvoiceResult] = useState<CustomerOutstandingInvoicesResult | null>(null);
    const [invoiceLoading, setInvoiceLoading] = useState(false);
    const [invoiceError, setInvoiceError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);
        getCustomerOutstanding(filters, controller.signal)
            .then(setResult)
            .catch((requestError) => {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [filters]);

    useEffect(() => {
        if (!selected) {
            setInvoiceResult(null);
            return;
        }

        const controller = new AbortController();
        setInvoiceLoading(true);
        setInvoiceError(null);
        getCustomerOutstandingInvoices(selected.customerId, {
            ...filters,
            page: invoicePage,
            per_page: 10,
            currency_code: selected.currencyCode,
        }, controller.signal)
            .then(setInvoiceResult)
            .catch((requestError) => {
                if (!controller.signal.aborted) setInvoiceError(toApiError(requestError));
            })
            .finally(() => {
                if (!controller.signal.aborted) setInvoiceLoading(false);
            });

        return () => controller.abort();
    }, [filters, invoicePage, selected]);

    function applyFilters(event: FormEvent) {
        event.preventDefault();
        setSelected(null);
        setInvoicePage(1);
        setFilters({ ...draft, customer_id: draftCustomer?.id, page: 1, per_page: 25 });
    }

    function selectCustomer(row: CustomerOutstandingRow) {
        const isSelected = selected?.customerId === row.customer.id
            && selected.currencyCode === row.currency.code;
        setSelected(isSelected ? null : {
            customerId: row.customer.id,
            customerName: row.customer.name,
            currencyCode: row.currency.code,
        });
        setInvoicePage(1);
    }

    async function exportCsv() {
        setExporting(true);
        setExportError(null);

        try {
            const file = await downloadCustomerOutstandingCsv(filters);
            const url = URL.createObjectURL(file);
            const link = document.createElement('a');
            link.href = url;
            link.download = `customer-outstanding-${filters.as_of_date}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        } catch (requestError) {
            setExportError(toApiError(requestError));
        } finally {
            setExporting(false);
        }
    }

    const summary = result?.summary;

    return (
        <>
            <ContentHeader
                title="Customer Outstanding"
                description="Review unpaid customer invoices by customer and due date."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />

            <div className="space-y-5">
                <Panel title="Filters">
                    <form onSubmit={applyFilters}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="Aging as of"
                                type="date"
                                value={draft.as_of_date ?? ''}
                                max={localDateToday()}
                                hint="Outstanding uses current invoice balances; this date sets the aging cutoff."
                                onChange={(event) => setDraft((current) => ({ ...current, as_of_date: event.target.value || localDateToday() }))}
                            />
                            <CustomerLookupSelect
                                label="Search customer"
                                value={draftCustomer}
                                onChange={setDraftCustomer}
                                placeholder="Name, code, or customer number"
                                lookupKind="all"
                            />
                            <Select
                                label="Aging bucket"
                                value={draft.aging_bucket ?? ''}
                                placeholder="All aging buckets"
                                options={AGING_OPTIONS}
                                onChange={(event) => setDraft((current) => ({ ...current, aging_bucket: (event.target.value || undefined) as ReceivableAgingBucket | undefined }))}
                            />
                            <div className="flex items-end gap-2">
                                <Button type="submit">Apply filters</Button>
                                <Button type="button" variant="secondary" onClick={() => {
                                    setDraft(INITIAL_FILTERS);
                                    setFilters(INITIAL_FILTERS);
                                    setDraftCustomer(null);
                                    setSelected(null);
                                    setInvoicePage(1);
                                }}>Reset</Button>
                            </div>
                        </div>
                    </form>
                </Panel>

                {summary && <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard label="Customers with balance" value={String(summary.customer_count)} />
                    <SummaryCard label="Overdue invoices" value={String(summary.overdue_invoice_count)} />
                    {summary.totals_by_currency.map((total) => (
                        <SummaryCard
                            key={total.currency.code}
                            label={`Outstanding · ${total.currency.code}`}
                            value={<MoneyDisplay value={total.outstanding_total} currency={total.currency.code} />}
                            note={`Overdue: ${formatMoney(total.overdue_total, total.currency.code)}`}
                        />
                    ))}
                </div>}

                <Panel title="Outstanding by customer">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-slate-600">Select a customer row to see its unpaid invoices. Amounts stay separate by currency.</p>
                        <Button type="button" variant="secondary" loading={exporting} loadingLabel="Preparing CSV..." onClick={() => void exportCsv()}>
                            Export CSV
                        </Button>
                    </div>
                    {exportError && <ErrorAlert error={exportError} />}
                    <ErrorAlert error={error} />
                    {loading ? <LoadingState label="Loading customer outstanding..." /> : error ? null : (
                        <DataTable
                            rows={result?.data ?? []}
                            rowKey={(row) => `${row.customer.id}-${row.currency.code}`}
                            emptyMessage="No customer invoices have an outstanding balance for these filters."
                            mobileSummary={(row) => <CustomerToggle row={row} selected={selected} onSelect={selectCustomer} />}
                            mobileDetails={(row) => <div className="grid grid-cols-2 gap-3">
                                <MobileValue label="Code" value={row.customer.code} />
                                <MobileValue label="Currency" value={row.currency.code} />
                                <MobileValue label="Open invoices" value={String(row.invoice_count)} />
                                <MobileValue label="Current" value={<MoneyDisplay value={row.current} currency={row.currency.code} />} />
                                <MobileValue label="1–30 days" value={<MoneyDisplay value={row.days_1_30} currency={row.currency.code} />} />
                                <MobileValue label="31–60 days" value={<MoneyDisplay value={row.days_31_60} currency={row.currency.code} />} />
                                <MobileValue label="61–90 days" value={<MoneyDisplay value={row.days_61_90} currency={row.currency.code} />} />
                                <MobileValue label="90+ days" value={<MoneyDisplay value={row.over_90} currency={row.currency.code} />} />
                                <MobileValue label="Outstanding" value={<MoneyDisplay value={row.outstanding_total} currency={row.currency.code} />} />
                            </div>}
                            columns={[
                                { key: 'customer', header: 'Customer', render: (row) => <CustomerToggle row={row} selected={selected} onSelect={selectCustomer} /> },
                                { key: 'code', header: 'Code', render: (row) => row.customer.code },
                                { key: 'currency', header: 'Currency', render: (row) => row.currency.code },
                                { key: 'invoices', header: 'Open invoices', render: (row) => row.invoice_count, className: 'text-right' },
                                { key: 'current', header: 'Current', render: (row) => <MoneyDisplay value={row.current} currency={row.currency.code} />, className: 'text-right' },
                                { key: 'days_1_30', header: '1–30', render: (row) => <MoneyDisplay value={row.days_1_30} currency={row.currency.code} />, className: 'text-right' },
                                { key: 'days_31_60', header: '31–60', render: (row) => <MoneyDisplay value={row.days_31_60} currency={row.currency.code} />, className: 'text-right' },
                                { key: 'days_61_90', header: '61–90', render: (row) => <MoneyDisplay value={row.days_61_90} currency={row.currency.code} />, className: 'text-right' },
                                { key: 'over_90', header: '90+', render: (row) => <MoneyDisplay value={row.over_90} currency={row.currency.code} />, className: 'text-right' },
                                { key: 'total', header: 'Outstanding', render: (row) => <MoneyDisplay value={row.outstanding_total} currency={row.currency.code} />, className: 'text-right font-semibold' },
                            ]}
                        />
                    )}
                    <Pagination meta={result?.meta} onPageChange={(page) => setFilters((current) => ({ ...current, page }))} />
                </Panel>

                {selected && <Panel title={`Invoices · ${selected.customerName} · ${selected.currencyCode}`}>
                    <ErrorAlert error={invoiceError} />
                    {invoiceLoading ? <LoadingState label="Loading customer invoices..." /> : (
                        <DataTable
                            rows={invoiceResult?.data ?? []}
                            rowKey={(row) => row.invoice.id}
                            emptyMessage="No unpaid invoices match this aging bucket."
                            columns={invoiceColumns}
                        />
                    )}
                    <Pagination meta={invoiceResult?.meta} onPageChange={setInvoicePage} />
                </Panel>}
            </div>
        </>
    );
}

function CustomerToggle({ row, selected, onSelect }: {
    row: CustomerOutstandingRow;
    selected: { customerId: number; currencyCode: string } | null;
    onSelect: (row: CustomerOutstandingRow) => void;
}) {
    const isSelected = selected?.customerId === row.customer.id && selected.currencyCode === row.currency.code;

    return <button
        type="button"
        className="text-left font-semibold text-sky-700 hover:underline"
        aria-expanded={isSelected}
        onClick={() => onSelect(row)}
    >
        {isSelected ? '▾ ' : '▸ '}{row.customer.name}
        <span className="mt-0.5 block text-xs font-normal text-slate-500">{row.customer.customer_number}</span>
    </button>;
}

function SummaryCard({ label, value, note }: { label: string; value: ReactNode; note?: string }) {
    return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="text-sm font-medium text-slate-600">{label}</div>
        <div className="mt-2 text-xl font-bold tabular-nums text-slate-950">{value}</div>
        {note && <div className="mt-1 text-xs text-slate-500">{note}</div>}
    </div>;
}

function MobileValue({ label, value }: { label: string; value: ReactNode }) {
    return <div className="min-w-0"><div className="text-xs font-medium text-slate-500">{label}</div><div className="mt-0.5">{value}</div></div>;
}

const invoiceColumns = [
    { key: 'invoice', header: 'Invoice', render: (row: CustomerOutstandingInvoiceRow) => <Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${row.invoice.id}`}>{row.invoice.number}</Link> },
    { key: 'date', header: 'Invoice date', render: (row: CustomerOutstandingInvoiceRow) => row.invoice.date },
    { key: 'due', header: 'Due date', render: (row: CustomerOutstandingInvoiceRow) => row.invoice.due_date },
    { key: 'total', header: 'Invoice total', render: (row: CustomerOutstandingInvoiceRow) => <MoneyDisplay value={row.invoice_total} currency={row.currency.code} />, className: 'text-right' },
    { key: 'paid', header: 'Paid', render: (row: CustomerOutstandingInvoiceRow) => <MoneyDisplay value={row.paid_amount} currency={row.currency.code} />, className: 'text-right' },
    { key: 'credit', header: 'Credits applied', render: (row: CustomerOutstandingInvoiceRow) => <MoneyDisplay value={row.credit_allocated_amount} currency={row.currency.code} />, className: 'text-right' },
    { key: 'outstanding', header: 'Outstanding', render: (row: CustomerOutstandingInvoiceRow) => <MoneyDisplay value={row.outstanding_amount} currency={row.currency.code} />, className: 'text-right font-semibold' },
    { key: 'overdue', header: 'Days overdue', render: (row: CustomerOutstandingInvoiceRow) => row.days_overdue === 0 ? 'Not overdue' : row.days_overdue },
];

function localDateToday(): string {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

