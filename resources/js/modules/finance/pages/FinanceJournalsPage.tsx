import { useState } from 'react';
import { Link } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { listJournals, type JournalEntry } from '../financeApi';
import { financePermissions } from '../financePermissions';

const journalStatuses = ['draft', 'posted', 'reversed', 'cancelled'] as const;

export default function FinanceJournalsPage() {
    const auth = useAuth();
    const canCreate = hasPermission(auth, financePermissions.journalsCreate);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listJournals({
        search: debounced || undefined,
        status: status || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, status, page]);
    const columns: DataColumn<JournalEntry>[] = [
        { key: 'journal', header: 'Journal', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/finance/journals/${row.id}`}>{row.journal_number}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.journal_date) },
        { key: 'type', header: 'Type', render: (row) => row.journal_type.replaceAll('_', ' ') },
        { key: 'source', header: 'Source', render: (row) => row.source_number ?? row.source_type ?? '-' },
        { key: 'debit', header: 'Debit', render: (row) => <span className="tabular-nums">{row.total_debit}</span> },
        { key: 'credit', header: 'Credit', render: (row) => <span className="tabular-nums">{row.total_credit}</span> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return <>
        <ContentHeader
            title="Journal entries"
            description="Draft, posted, cancelled, and reversed accounting journals."
            actions={canCreate ? <Link className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700" to="/finance/journals/create">New journal</Link> : undefined}
        />
        <div className="mb-4 grid gap-3 md:grid-cols-[minmax(16rem,1fr)_14rem]">
            <Input type="search" placeholder="Search journal or source number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={journalStatuses.map((value) => ({ value, label: value }))} placeholder="All statuses" />
        </div>
        <ErrorAlert error={result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;
}
