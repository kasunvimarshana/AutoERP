import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
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

export default function InvoiceListPage() {
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const scope = useMemo(() => ({
        invoice_type: searchParams.get('invoice_type') || undefined,
        direction: searchParams.get('direction') || undefined,
        balance_status: searchParams.get('balance_status') || undefined,
    }), [searchParams]);
    const context = invoiceContext(scope.invoice_type, scope.direction);
    const detailSearch = searchParams.toString();
    const result = useApi((signal) => listInvoices({
        search: debounced || undefined,
        ...scope,
        page,
        per_page: 25,
    }, signal), [debounced, page, scope]);
    useEffect(() => {
        setSearch('');
        setPage(1);
    }, [scope.invoice_type, scope.direction, scope.balance_status]);
    const columns: DataColumn<Invoice>[] = [
        { key: 'invoice', header: 'Invoice', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${row.id}${detailSearch ? `?${detailSearch}` : ''}`}>{row.invoice_number ?? 'Invoice'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.invoice_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${row.invoice_type ?? '-'} / ${row.direction ?? '-'}` },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total} /> },
        { key: 'balance', header: 'Balance', render: (row) => <MoneyDisplay value={row.balance_due ?? (row.balance?.remaining_amount as string)} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            <ContentHeader title={context.title} description={context.description} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search invoice number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage={`No ${context.title.toLowerCase()} found for this scope.`} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function invoiceContext(invoiceType?: string, direction?: string) {
    if (invoiceType === 'sales') return { title: 'Sales Invoices', description: 'Customer invoices created by Sales.' };
    if (invoiceType === 'purchase') return { title: 'Supplier Invoices', description: 'Supplier invoices created by Purchase.' };
    if (invoiceType === 'service') return { title: 'Vehicle Service Invoices', description: 'Customer invoices created from service jobs.' };
    if (invoiceType === 'rental') return { title: 'Rental Invoices & Payables', description: 'Customer invoices and supplier payables created from rental agreements.' };
    if (direction === 'outbound') return { title: 'Receivables', description: 'Customer-facing invoices from every operational module.' };
    if (direction === 'inbound') return { title: 'Payables', description: 'Supplier-facing invoices from every operational module.' };
    return { title: 'Invoices', description: 'Consolidated invoices from every operational module.' };
}
