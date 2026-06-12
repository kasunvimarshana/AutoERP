import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { listJournals, type JournalEntry } from '../financeApi';

export default function FinanceReversalsPage() {
    const result = useApi((signal) => listJournals({ per_page: 50, journal_type: 'reversal' }, signal), []);
    const columns: DataColumn<JournalEntry>[] = [
        { key: 'journal', header: 'Reversal journal', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/finance/journals/${row.id}`}>{row.journal_number}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.journal_date) },
        { key: 'source', header: 'Source', render: (row) => row.source_number ?? row.source_type ?? '-' },
        { key: 'reason', header: 'Reason', render: (row) => row.reversal_reason ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    ];

    return <>
        <ContentHeader title="Reversals" description="Posted reversal journals and source references." />
        <ErrorAlert error={result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} rowKey={(row) => row.id} columns={columns} />}
    </>;
}
