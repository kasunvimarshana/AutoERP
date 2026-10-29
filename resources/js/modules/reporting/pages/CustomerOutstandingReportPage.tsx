import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { formatMoney } from '@/shared/utils/formatMoney';
import { businessDateInputValue, businessTimeZone } from '@/shared/utils/businessDate';
import { getCustomerOutstanding, getCustomerOutstandingInvoices, getCustomerOutstandingLocations, downloadCustomerOutstandingCsv } from '@/modules/finance/financeApi';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { CustomerOutstandingGroupTable, type CustomerOutstandingSelection } from '@/modules/reporting/components/CustomerOutstandingGroupTable';
import type {
    CustomerOutstandingInvoicesResult,
    CustomerOutstandingParams,
    CustomerOutstandingReportResult,
    CustomerOutstandingRow,
    ReceivableAgingBucket,
} from '@/modules/finance/financeTypes';

const initialFilters = (): CustomerOutstandingParams => ({
    as_of_date: businessDateInputValue(),
    timezone: businessTimeZone(),
    page: 1,
    per_page: 25,
});

const AGING_OPTIONS = [
    { value: 'current', label: 'Current' },
    { value: '1_30', label: '1–30 days overdue' },
    { value: '31_60', label: '31–60 days overdue' },
    { value: '61_90', label: '61–90 days overdue' },
    { value: '90_plus', label: '90+ days overdue' },
];

export default function CustomerOutstandingReportPage() {
    const [filters, setFilters] = useState<CustomerOutstandingParams>(initialFilters);
    const [draft, setDraft] = useState<CustomerOutstandingParams>(filters);
    const [draftCustomer, setDraftCustomer] = useState<CustomerSummary | null>(null);
    const [locations, setLocations] = useState<Array<{ id: number; name: string }>>([]);
    const [result, setResult] = useState<CustomerOutstandingReportResult | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [exporting, setExporting] = useState(false);
    const [exportError, setExportError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<CustomerOutstandingSelection | null>(null);
    const [invoicePage, setInvoicePage] = useState(1);
    const [invoiceResult, setInvoiceResult] = useState<CustomerOutstandingInvoicesResult | null>(null);
    const [invoiceLoading, setInvoiceLoading] = useState(false);
    const [invoiceError, setInvoiceError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        getCustomerOutstandingLocations(controller.signal)
            .then((items) => { if (!controller.signal.aborted) setLocations(items); })
            .catch((requestError) => { if (!controller.signal.aborted) setError(toApiError(requestError)); });

        return () => controller.abort();
    }, []);

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
            location_id: selected.locationId ?? undefined,
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
        setFilters({
            ...draft,
            customer_id: draftCustomer?.id,
            page: 1,
            per_page: 25,
        });
    }

    function selectCustomer(row: CustomerOutstandingRow) {
        const isSelected = selected?.customerId === row.customer.id
            && selected.currencyCode === row.currency.code
            && selected.locationId === row.location.id;
        setSelected(isSelected ? null : {
            customerId: row.customer.id,
            currencyCode: row.currency.code,
            locationId: row.location.id,
        });
        setInvoicePage(1);
        setInvoiceResult(null);
        setInvoiceError(null);
    }

    function changeInvoicePage(page: number) {
        setInvoiceResult(null);
        setInvoiceError(null);
        setInvoicePage(page);
    }

    async function exportCsv() {
        setExporting(true);
        setExportError(null);

        try {
            const file = await downloadCustomerOutstandingCsv(filters);
            const url = URL.createObjectURL(file);
            const link = document.createElement('a');
            link.href = url;
            link.download = `customer-outstanding-as-at-${filters.as_of_date ?? businessDateInputValue()}.csv`;
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
    const locationGroups = groupByLocation(result?.data ?? []);

    return (
        <>
            <ContentHeader
                title="Customer Outstanding"
                description="Review customer invoice balances as at a selected date."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />

            <div className="space-y-5">
                <Panel title="Filters">
                    <form onSubmit={applyFilters}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Input
                                label="As at date"
                                type="date"
                                value={draft.as_of_date ?? ''}
                                max={businessDateInputValue()}
                                hint="Includes invoices and settlements effective by this date."
                                onChange={(event) => setDraft((current) => ({ ...current, as_of_date: event.target.value || businessDateInputValue() }))}
                            />
                            <CustomerLookupSelect
                                label="Search customer"
                                value={draftCustomer}
                                onChange={setDraftCustomer}
                                placeholder="Name, code, or customer number"
                            />
                            <Select
                                label="Location"
                                value={draft.location_id?.toString() ?? ''}
                                placeholder="All accessible locations"
                                options={locations.map((location) => ({ value: String(location.id), label: location.name }))}
                                onChange={(event) => setDraft((current) => ({ ...current, location_id: event.target.value ? Number(event.target.value) : undefined }))}
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
                                    const reset = initialFilters();
                                    setDraft(reset);
                                    setFilters(reset);
                                    setDraftCustomer(null);
                                    setSelected(null);
                                    setInvoicePage(1);
                                }}>Reset</Button>
                            </div>
                        </div>
                        <p className="mt-3 text-xs text-slate-500">All earlier customer invoices with a balance as at the selected date are included.</p>
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
                    {loading ? <LoadingState label="Loading customer outstanding..." /> : error ? null : locationGroups.length === 0 ? (
                        <p className="py-8 text-center text-sm text-slate-600">No customer invoices have an outstanding balance as at this date.</p>
                    ) : <div className="space-y-6">{locationGroups.map((group) => <section key={group.key} className="border-t border-slate-300 pt-4">
                        <h2 className="mb-3 text-base font-semibold text-slate-900">{group.name}</h2>
                        <CustomerOutstandingGroupTable
                            rows={group.rows}
                            selected={selected}
                            invoiceResult={invoiceResult}
                            invoiceLoading={invoiceLoading}
                            invoiceError={invoiceError}
                            onSelect={selectCustomer}
                            onInvoicePageChange={changeInvoicePage}
                        />
                        <div className="mt-2 flex flex-wrap gap-4 border-t border-slate-200 pt-3 text-sm font-semibold text-slate-800">
                            {summary?.totals_by_location.filter((total) => total.location.id === group.id).map((total) => (
                                <span key={total.currency.code}>{group.name} total, all pages ({total.currency.code}): {formatMoney(total.outstanding_total, total.currency.code)}</span>
                            ))}
                        </div>
                    </section>)}</div>}
                    <Pagination meta={result?.meta} onPageChange={(page) => {
                        setSelected(null);
                        setInvoicePage(1);
                        setFilters((current) => ({ ...current, page }));
                    }} />
                </Panel>
            </div>
        </>
    );
}

function SummaryCard({ label, value, note }: { label: string; value: ReactNode; note?: string }) {
    return <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="text-sm font-medium text-slate-600">{label}</div>
        <div className="mt-2 text-xl font-bold tabular-nums text-slate-950">{value}</div>
        {note && <div className="mt-1 text-xs text-slate-500">{note}</div>}
    </div>;
}

function groupByLocation(rows: CustomerOutstandingRow[]): Array<{ key: string; id: number | null; name: string; rows: CustomerOutstandingRow[] }> {
    const groups: Array<{ key: string; id: number | null; name: string; rows: CustomerOutstandingRow[] }> = [];
    for (const row of rows) {
        const key = String(row.location.id ?? 'unassigned');
        const last = groups.at(-1);
        if (last?.key === key) {
            last.rows.push(row);
        } else {
            groups.push({ key, id: row.location.id, name: row.location.name, rows: [row] });
        }
    }

    return groups;
}

