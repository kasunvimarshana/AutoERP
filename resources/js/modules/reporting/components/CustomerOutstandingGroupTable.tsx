import { Fragment, useId, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import type { ApiError } from '@/shared/api/apiError';
import type {
    CustomerOutstandingInvoiceRow,
    CustomerOutstandingInvoicesResult,
    CustomerOutstandingRow,
} from '@/modules/finance/financeTypes';

export interface CustomerOutstandingSelection {
    customerId: number;
    currencyCode: string;
    locationId: number | null;
}

interface Props {
    rows: CustomerOutstandingRow[];
    selected: CustomerOutstandingSelection | null;
    invoiceResult: CustomerOutstandingInvoicesResult | null;
    invoiceLoading: boolean;
    invoiceError: ApiError | null;
    onSelect: (row: CustomerOutstandingRow) => void;
    onInvoicePageChange: (page: number) => void;
}

const customerHeaders = ['Customer', 'Open invoices', 'Current', '1–30', '31–60', '61–90', '90+', 'Outstanding'];

const invoiceColumns = [
    {
        key: 'invoice',
        header: 'Invoice',
        render: (row: CustomerOutstandingInvoiceRow) => <>
            <Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${row.invoice.id}`}>{row.invoice.number}</Link>
            {row.invoice.service_job_number && <span className="block text-xs text-slate-500">Service job: {row.invoice.service_job_number}</span>}
        </>,
    },
    { key: 'date', header: 'Invoice date', render: (row: CustomerOutstandingInvoiceRow) => row.invoice.date },
    { key: 'age', header: 'No. of days', render: (row: CustomerOutstandingInvoiceRow) => row.invoice_age_days, className: 'text-right' },
    { key: 'overdue', header: 'Due days', render: (row: CustomerOutstandingInvoiceRow) => row.days_overdue === 0 ? 'Not overdue' : row.days_overdue, className: 'text-right' },
    { key: 'outstanding', header: 'Balance', render: (row: CustomerOutstandingInvoiceRow) => <MoneyDisplay value={row.outstanding_amount} currency={row.currency.code} />, className: 'text-right font-semibold' },
];

export function CustomerOutstandingGroupTable({ rows, selected, invoiceResult, invoiceLoading, invoiceError, onSelect, onInvoicePageChange }: Props) {
    const idPrefix = useId();
    const isSelected = (row: CustomerOutstandingRow) => selected?.customerId === row.customer.id
        && selected.currencyCode === row.currency.code
        && selected.locationId === row.location.id;
    const detailsId = (row: CustomerOutstandingRow, view: 'mobile' | 'desktop') => `${idPrefix}-${view}-${row.customer.id}-${row.currency.code.replace(/[^a-zA-Z0-9]/g, '-')}`;

    return <>
        <div className="space-y-2 md:hidden">
            {rows.map((row) => <article key={`${row.customer.id}-${row.currency.code}`} className="rounded-lg border border-slate-200 bg-white p-4">
                <div className="flex items-start justify-between gap-3">
                    <CustomerToggle row={row} open={isSelected(row)} detailsId={detailsId(row, 'mobile')} onSelect={onSelect} />
                    <div className="shrink-0 text-right text-sm font-semibold"><MoneyDisplay value={row.outstanding_total} currency={row.currency.code} /></div>
                </div>
                <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <MobileValue label="Open invoices" value={row.invoice_count} />
                    <MobileValue label="Current" value={<MoneyDisplay value={row.current} currency={row.currency.code} />} />
                    <MobileValue label="1–30 days" value={<MoneyDisplay value={row.days_1_30} currency={row.currency.code} />} />
                    <MobileValue label="31–60 days" value={<MoneyDisplay value={row.days_31_60} currency={row.currency.code} />} />
                    <MobileValue label="61–90 days" value={<MoneyDisplay value={row.days_61_90} currency={row.currency.code} />} />
                    <MobileValue label="90+ days" value={<MoneyDisplay value={row.over_90} currency={row.currency.code} />} />
                </div>
                {isSelected(row) && <div id={detailsId(row, 'mobile')} className="mt-4 border-t border-slate-200 pt-4">
                    <InvoiceDetails row={row} result={invoiceResult} loading={invoiceLoading} error={invoiceError} onPageChange={onInvoicePageChange} />
                </div>}
            </article>)}
        </div>
        <div className="hidden overflow-x-auto rounded-lg border border-slate-200 bg-white md:block">
            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>{customerHeaders.map((header) => <th key={header} scope="col" className={header === 'Customer' ? 'px-4 py-3' : 'px-4 py-3 text-right'}>{header}</th>)}</tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rows.map((row) => <Fragment key={`${row.customer.id}-${row.currency.code}`}>
                        <tr className={isSelected(row) ? 'bg-sky-50' : undefined}>
                            <td className="px-4 py-3"><CustomerToggle row={row} open={isSelected(row)} detailsId={detailsId(row, 'desktop')} onSelect={onSelect} /></td>
                            <td className="px-4 py-3 text-right">{row.invoice_count}</td>
                            <td className="px-4 py-3 text-right"><MoneyDisplay value={row.current} currency={row.currency.code} /></td>
                            <td className="px-4 py-3 text-right"><MoneyDisplay value={row.days_1_30} currency={row.currency.code} /></td>
                            <td className="px-4 py-3 text-right"><MoneyDisplay value={row.days_31_60} currency={row.currency.code} /></td>
                            <td className="px-4 py-3 text-right"><MoneyDisplay value={row.days_61_90} currency={row.currency.code} /></td>
                            <td className="px-4 py-3 text-right"><MoneyDisplay value={row.over_90} currency={row.currency.code} /></td>
                            <td className="px-4 py-3 text-right font-semibold"><MoneyDisplay value={row.outstanding_total} currency={row.currency.code} /></td>
                        </tr>
                        {isSelected(row) && <tr><td colSpan={customerHeaders.length} className="bg-slate-50 px-4 py-4">
                            <div id={detailsId(row, 'desktop')}>
                                <InvoiceDetails row={row} result={invoiceResult} loading={invoiceLoading} error={invoiceError} onPageChange={onInvoicePageChange} />
                            </div>
                        </td></tr>}
                    </Fragment>)}
                </tbody>
            </table>
        </div>
    </>;
}

function CustomerToggle({ row, open, detailsId, onSelect }: {
    row: CustomerOutstandingRow;
    open: boolean;
    detailsId: string;
    onSelect: (row: CustomerOutstandingRow) => void;
}) {
    return <button type="button" className="text-left font-semibold text-sky-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600" aria-expanded={open} aria-controls={open ? detailsId : undefined} onClick={() => onSelect(row)}>
        <span aria-hidden="true">{open ? '▾ ' : '▸ '}</span>{row.customer.name}
        <span className="mt-0.5 block text-xs font-normal text-slate-500">{row.customer.customer_number}</span>
    </button>;
}

function InvoiceDetails({ row, result, loading, error, onPageChange }: {
    row: CustomerOutstandingRow;
    result: CustomerOutstandingInvoicesResult | null;
    loading: boolean;
    error: ApiError | null;
    onPageChange: (page: number) => void;
}) {
    return <div className="space-y-3">
        <h3 className="font-semibold text-slate-900">Invoices · {row.customer.name} · {row.currency.code}</h3>
        <ErrorAlert error={error} />
        {loading || (!result && !error) ? <LoadingState label="Loading customer invoices..." /> : error ? null : <>
            <DataTable rows={result?.data ?? []} rowKey={(invoice) => invoice.invoice.id} emptyMessage="No unpaid invoices match this aging bucket." columns={invoiceColumns} />
            <Pagination meta={result?.meta} onPageChange={onPageChange} />
        </>}
    </div>;
}

function MobileValue({ label, value }: { label: string; value: ReactNode }) {
    return <div className="min-w-0"><div className="text-xs font-medium text-slate-500">{label}</div><div className="mt-0.5">{value}</div></div>;
}
