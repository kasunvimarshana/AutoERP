import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import type { ReactNode } from 'react';
import { listInvoices, type Invoice } from '../invoiceApi';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Input } from '@/shared/components/Input';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { LinkButton } from '@/shared/components/Button';

const invoiceViews = {
    supplier: {
        title: 'Supplier Invoices',
        description: 'Purchase invoices payable to suppliers.',
        params: { invoice_type: 'purchase', direction: 'inbound' },
        action: { to: '/purchase/invoices/create', label: 'Create supplier invoice' },
    },
    customer: {
        title: 'Customer Invoices',
        description: 'Sales invoices issued to customers.',
        params: { invoice_type: 'sales', direction: 'outbound' },
        action: undefined,
    },
    service: {
        title: 'Service Invoices',
        description: 'Customer invoices generated from completed vehicle service jobs.',
        params: { invoice_type: 'service', direction: 'outbound' },
        action: { to: '/vehicle-service/jobs', label: 'Open service jobs' },
    },
    'rental-payable': {
        title: 'Owner / Supplier Payables',
        description: 'Inbound rental payables from owner and supplier agreements.',
        params: { invoice_type: 'rental', direction: 'inbound' },
        action: { to: '/vehicle-rental/lessor-agreements', label: 'Open lessor agreements' },
    },
    'rental-customer': {
        title: 'Customer Invoices',
        description: 'Outbound invoices generated from customer rental agreements.',
        params: { invoice_type: 'rental', direction: 'outbound' },
        action: { to: '/vehicle-rental/lessee-agreements', label: 'Open lessee agreements' },
    },
} as const;

type InvoiceViewKey = keyof typeof invoiceViews;
type InvoiceView = (typeof invoiceViews)[InvoiceViewKey];

export function InvoiceListWorkspace({ viewKey: forcedViewKey, renderHeader, rowHref }: {
    viewKey?: InvoiceViewKey;
    renderHeader?: (view: InvoiceView | undefined) => ReactNode;
    rowHref?: (invoice: Invoice) => string;
}) {
    const [searchParams] = useSearchParams();
    const viewKey = forcedViewKey ?? (searchParams.get('view') as InvoiceViewKey | null);
    const view = viewKey ? invoiceViews[viewKey] : undefined;
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listInvoices({
        search: debounced || undefined,
        ...view?.params,
        page,
        per_page: 25,
    }, signal), [debounced, page, viewKey]);
    const columns: DataColumn<Invoice>[] = [
        { key: 'invoice', header: 'Invoice', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={rowHref ? rowHref(row) : `/invoices/${row.id}`}>{row.invoice_number ?? 'Invoice'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.invoice_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${row.invoice_type ?? '-'} / ${row.direction ?? '-'}` },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total} /> },
        { key: 'balance', header: 'Balance', render: (row) => <MoneyDisplay value={row.balance_due ?? (row.balance?.remaining_amount as string)} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            {renderHeader ? renderHeader(view) : (
                <ContentHeader
                    title={view?.title ?? 'Invoices'}
                    description={view?.description ?? 'Invoice list and on-demand financial relations.'}
                    actions={view?.action ? <LinkButton to={view.action.to}>{view.action.label}</LinkButton> : undefined}
                />
            )}
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search invoice number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} rowHref={(row) => rowHref ? rowHref(row) : `/invoices/${row.id}`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

export default function InvoiceListPage() {
    return <InvoiceListWorkspace />;
}
