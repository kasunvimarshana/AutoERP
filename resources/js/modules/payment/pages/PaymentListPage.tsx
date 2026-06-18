import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { listPayments, type Payment } from '../paymentApi';
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
import { humanize, readableRelation } from '@/shared/utils/object';
import { LinkButton } from '@/shared/components/Button';

const paymentViews = {
    supplier: {
        title: 'Supplier Payments',
        description: 'Outbound payments created for supplier liabilities.',
        params: { payment_type: 'supplier_payment', direction: 'outbound' },
        action: { to: '/purchase/payments/create', label: 'Create supplier payment' },
    },
    customer: {
        title: 'Customer Receipts',
        description: 'Inbound receipts collected from sales customers.',
        params: { payment_type: 'customer_receipt', direction: 'inbound' },
        action: { to: '/sales/payments/prepare', label: 'Prepare customer receipt' },
    },
    service: {
        title: 'Customer Receipts',
        description: 'Receipts collected against vehicle service invoices.',
        params: { payment_type: 'service_receipt', direction: 'inbound' },
        action: { to: '/vehicle-service/jobs', label: 'Open service jobs' },
    },
    rental: {
        title: 'Rental Settlements',
        description: 'Receipts and settlements created from vehicle rental agreements.',
        params: { payment_type: 'rental_receipt' },
        action: { to: '/vehicle-rental/agreements', label: 'Open rental agreements' },
    },
} as const;

export default function PaymentListPage() {
    const [searchParams] = useSearchParams();
    const viewKey = searchParams.get('view') as keyof typeof paymentViews | null;
    const view = viewKey ? paymentViews[viewKey] : undefined;
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listPayments({
        search: debounced || undefined,
        ...view?.params,
        page,
        per_page: 25,
    }, signal), [debounced, page, viewKey]);
    const columns: DataColumn<Payment>[] = [
        { key: 'payment', header: 'Payment', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/${row.id}`}>{row.payment_number ?? 'Payment'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.payment_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${humanize(row.payment_type)} / ${humanize(row.direction)}` },
        { key: 'total', header: 'Amount', render: (row) => <MoneyDisplay value={row.total_amount} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            <ContentHeader
                title={view?.title ?? 'Payments'}
                description={view?.description ?? 'Payment activity with on-demand allocation data.'}
                actions={view?.action
                    ? <LinkButton to={view.action.to}>{view.action.label}</LinkButton>
                    : <LinkButton to="/payments/create">New payment</LinkButton>}
            />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search payment or reference number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} rowHref={(row) => `/payments/${row.id}`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
