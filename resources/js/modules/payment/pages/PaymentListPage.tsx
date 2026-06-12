import { useState } from 'react';
import { Link } from 'react-router-dom';
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
import { readableRelation } from '@/shared/utils/object';
import { Button } from '@/shared/components/Button';

export default function PaymentListPage() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listPayments({ search: debounced || undefined, page, per_page: 25 }, signal), [debounced, page]);
    const columns: DataColumn<Payment>[] = [
        { key: 'payment', header: 'Payment', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/${row.id}`}>{row.payment_number ?? 'Payment'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.payment_date) },
        { key: 'party', header: 'Party', render: (row) => readableRelation(row.party) },
        { key: 'type', header: 'Type', render: (row) => `${row.payment_type ?? '-'} / ${row.direction ?? '-'}` },
        { key: 'total', header: 'Amount', render: (row) => <MoneyDisplay value={row.total_amount} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];
    return (
        <>
            <ContentHeader title="Payments" description="Payment activity with on-demand allocation data." actions={<Link to="/payments/create"><Button>New payment</Button></Link>} />
            <div className="mb-4 max-w-md"><Input type="search" placeholder="Search payment or reference number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
