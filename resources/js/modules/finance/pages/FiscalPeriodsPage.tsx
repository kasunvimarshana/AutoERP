import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { listFiscalPeriods, updateFiscalPeriodStatus, type FiscalPeriod } from '../financeApi';

const statuses = ['open', 'locked', 'closed'] as const;

export default function FiscalPeriodsPage() {
    const [page, setPage] = useState(1);
    const [status, setStatus] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [savingId, setSavingId] = useState<number | null>(null);
    const result = useApi((signal) => listFiscalPeriods({ page, per_page: 25, status: status || undefined }, signal), [page, status]);
    const columns: DataColumn<FiscalPeriod>[] = [
        { key: 'period', header: 'Period', render: (row) => `${row.name} / ${row.fiscal_year?.name ?? ''}` },
        { key: 'dates', header: 'Dates', render: (row) => `${formatDate(row.start_date)} - ${formatDate(row.end_date)}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'change', header: 'Change status', render: (row) => <div className="flex flex-wrap gap-2">{statuses.map((next) => <Button key={next} type="button" variant={next === 'closed' ? 'danger' : 'secondary'} disabled={row.status === next || row.status === 'year_closed'} loading={savingId === row.id} onClick={() => void change(row, next)}>{next.replace('_', ' ')}</Button>)}</div> },
    ];

    async function change(period: FiscalPeriod, next: string) {
        setSavingId(period.id);
        setError(null);
        try {
            await updateFiscalPeriodStatus(period.id, next);
            result.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSavingId(null);
        }
    }

    return <>
        <ContentHeader title="Fiscal periods" description="Open, locked, closed, and year-closed accounting periods." />
        <div className="mb-4 max-w-xs"><Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} placeholder="All statuses" options={statuses.map((value) => ({ value, label: value.replace('_', ' ') }))} /></div>
        <ErrorAlert error={error ?? result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} rowKey={(row) => row.id} columns={columns} />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;
}
