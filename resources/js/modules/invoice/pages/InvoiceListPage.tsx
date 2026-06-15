import { useState } from 'react';
import { Link } from 'react-router-dom';
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
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listInvoices({ search: debounced || undefined, page, per_page: 25 }, signal), [debounced, page]);
    const columns: DataColumn<Invoice>[] = [
        { key: 'invoice', header: 'Invoice', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/invoices/${row.id}`}>{row.invoice_number ?? 'Invoice'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.invoice_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${row.invoice_type ?? '-'} / ${row.direction ?? '-'}` },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total} /> },
        { key: 'balance', header: 'Balance', render: (row) => <MoneyDisplay value={row.balance_due ?? (row.balance?.remaining_amount as string)} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            <ContentHeader title="Invoices" description="Invoice list and on-demand financial relations." />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search invoice number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
