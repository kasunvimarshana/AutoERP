import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { listPurchaseDebitNotes, type PurchaseDebitNote } from '../purchaseApi';

export default function PurchaseDebitNoteListPage() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const result = useApi((signal) => listPurchaseDebitNotes({ page, search, per_page: 15 }, signal), [page, search]);
    const columns: DataColumn<PurchaseDebitNote>[] = [
        { key: 'number', header: 'Debit note', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/debit-notes/${row.id}`}>{row.debit_note_number ?? `Debit note #${row.id}`}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.debit_note_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'amount', header: 'Amount', render: (row) => formatMoney(row.amount) },
        { key: 'remaining', header: 'Remaining', render: (row) => formatMoney(row.remaining_amount) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => <Link to={`/purchase/debit-notes/${row.id}`}><Button type="button" variant="ghost">View</Button></Link> },
    ];
    return (
        <div className="space-y-5">
            <ContentHeader title="Purchase debit notes" actions={<Link to="/purchase/returns/create"><Button>New debit note</Button></Link>} />
            <ErrorAlert error={result.error} />
            <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </div>
    );
}
