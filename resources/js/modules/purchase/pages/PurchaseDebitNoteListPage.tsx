import { useState } from 'react';
import { Link } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { listPurchaseDebitNotes, type PurchaseDebitNote } from '../purchaseApi';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

export default function PurchaseDebitNoteListPage() {
    const auth = useAuth();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search);
    const result = useApi((signal) => listPurchaseDebitNotes({ page, search: debouncedSearch || undefined, per_page: 15 }, signal), [page, debouncedSearch]);
    const columns: DataColumn<PurchaseDebitNote>[] = [
        { key: 'number', header: 'Debit note', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/debit-notes/${row.id}`}>{row.debit_note_number ?? 'Debit note'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.debit_note_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'amount', header: 'Amount', render: (row) => formatMoney(row.amount) },
        { key: 'remaining', header: 'Remaining', render: (row) => formatMoney(row.remaining_amount) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => <LinkButton to={`/purchase/debit-notes/${row.id}`} variant="ghost">View</LinkButton> },
    ];
    const actions = hasPurchasePermission(auth.permissions, purchasePermissions.debitNotesCreate)
        ? <LinkButton to="/purchase/debit-notes/create">New debit note</LinkButton>
        : undefined;
    return (
        <div className="space-y-5">
            <ContentHeader title="Purchase debit notes" actions={actions} />
            <ErrorAlert error={result.error} />
            <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </div>
    );
}
