import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { changeAccountingPeriodStatus, listAccountingPeriods, type AccountingPeriod } from '../financeApi';
import { financePermissions } from '../financePermissions';

export default function AccountingPeriodsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, financePermissions.accountingPeriodsManage);
    const [page, setPage] = useState(1);
    const periods = useApi((signal) => listAccountingPeriods(page, signal), [page]);
    const [selected, setSelected] = useState<AccountingPeriod | null>(null);
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const action = selected?.status === 'closed' ? 'reopen' : 'close';
    const submit = async () => {
        if (!selected || !reason.trim()) return;
        setBusy(true);
        setError(null);
        try {
            await changeAccountingPeriodStatus(selected, action, reason.trim());
            setSelected(null);
            setReason('');
            periods.reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setBusy(false);
        }
    };

    return (
        <>
            <ContentHeader title="Accounting periods" description="Reopen a closed period to enter missed transactions, then close it again after reviewing the reports." />
            <ErrorAlert error={error ?? periods.error} />
            {selected && <div className="mb-4 max-w-xl space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                <p className="font-semibold">{action === 'reopen' ? 'Reopen' : 'Close'} {selected.name} ({selected.code})</p>
                <Input label="Reason" value={reason} required onChange={(event) => setReason(event.target.value)} />
                <div className="flex gap-2">
                    <Button loading={busy} disabled={!reason.trim()} onClick={submit}>{action === 'reopen' ? 'Reopen period' : 'Close period'}</Button>
                    <Button variant="secondary" disabled={busy} onClick={() => { setSelected(null); setReason(''); }}>Cancel</Button>
                </div>
            </div>}
            {periods.loading ? <LoadingState /> : <DataTable rows={periods.data?.data ?? []} rowKey={(period) => period.id} columns={[
                { key: 'period', header: 'Period', render: (period) => `${period.code} · ${period.name}` },
                { key: 'dates', header: 'Dates', render: (period) => `${formatDate(period.start_date)} – ${formatDate(period.end_date)}` },
                { key: 'scope', header: 'Applies to', render: (period) => period.scope === 'all_branches' ? 'All branches' : 'Current branch' },
                { key: 'status', header: 'Status', render: (period) => <StatusBadge status={period.status} /> },
                { key: 'action', header: 'Action', render: (period) => canManage
                    ? <Button size="sm" variant="secondary" disabled={busy} onClick={() => { setSelected(period); setReason(''); }}>
                        {period.status === 'closed' ? 'Reopen' : 'Close'}
                    </Button>
                    : null },
            ]} />}
            <Pagination meta={periods.data?.meta} onPageChange={setPage} />
        </>
    );
}
