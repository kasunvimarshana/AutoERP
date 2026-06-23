import { useCallback, useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { Textarea } from '@/shared/components/Textarea';
import { formatDate } from '@/shared/utils/formatDate';
import { cancelJournal, getJournal, postJournal, reverseJournal, type JournalEntry } from '../financeApi';

type Action = 'post' | 'cancel' | null;

export default function FinanceJournalDetailPage() {
    const journalId = Number(useParams().id);
    const [journal, setJournal] = useState<JournalEntry | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [action, setAction] = useState<Action>(null);
    const [showReversal, setShowReversal] = useState(false);
    const [reversalDate, setReversalDate] = useState(businessDateInputValue());
    const [reversalReason, setReversalReason] = useState('');

    const load = useCallback(async (signal?: AbortSignal) => {
        const data = await getJournal(journalId, signal);
        setJournal(data);
    }, [journalId]);

    useEffect(() => {
        const controller = new AbortController();
        void Promise.resolve().then(() => load(controller.signal))
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [load]);

    if (loading) return <LoadingState />;
    if (!journal) return <ErrorAlert error={error} />;

    async function executeAction() {
        if (!action) return;
        setSubmitting(true);
        setError(null);
        try {
            if (action === 'post') await postJournal(journalId);
            if (action === 'cancel') await cancelJournal(journalId);
            await load();
            setAction(null);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    async function reverse() {
        setSubmitting(true);
        setError(null);
        try {
            await reverseJournal(journalId, reversalDate, reversalReason);
            await load();
            setShowReversal(false);
            setReversalReason('');
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return <>
        <ContentHeader
            title={journal.journal_number}
            description="Journal entry and immutable ledger impact."
            actions={<div className="flex flex-wrap gap-2">
                {journal.can_edit && <LinkButton to={`/finance/journals/${journal.id}/edit`} variant="secondary">Edit draft</LinkButton>}
                {journal.can_post && <Button type="button" onClick={() => setAction('post')}>Post journal</Button>}
                {journal.can_cancel && <Button type="button" variant="danger" onClick={() => setAction('cancel')}>Cancel draft</Button>}
                {journal.can_reverse && <Button type="button" variant="danger" onClick={() => setShowReversal(true)}>Reverse</Button>}
            </div>}
        />
        <ErrorAlert error={error} />
        <Panel title="Journal summary">
            <DetailGrid items={[
                { label: 'Status', value: <StatusBadge status={journal.status} /> },
                { label: 'Date', value: formatDate(journal.journal_date) },
                { label: 'Type', value: journal.journal_type.replaceAll('_', ' ') },
                { label: 'Fiscal period', value: journal.fiscal_period ? `${journal.fiscal_period.name} (${journal.fiscal_period.status})` : '-' },
                { label: 'Source', value: journal.source_number ?? ([journal.source_module, journal.source_type].filter(Boolean).join(' / ') || '-') },
                { label: 'Description', value: journal.description ?? '-' },
                { label: 'Total debit', value: <MoneyDisplay value={journal.total_debit} /> },
                { label: 'Total credit', value: <MoneyDisplay value={journal.total_credit} /> },
                { label: 'Reversal reason', value: journal.reversal_reason ?? '-' },
            ]} />
        </Panel>
        <Panel title="Journal lines" className="mt-5">
            <DataTable rows={journal.lines ?? []} rowKey={(row) => row.id ?? row.line_number} columns={[
                { key: 'line', header: '#', render: (row) => row.line_number },
                { key: 'account', header: 'Account', render: (row) => row.account ? `${row.account.code} - ${row.account.name}` : '-' },
                { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
                { key: 'debit', header: 'Debit', render: (row) => <MoneyDisplay value={row.debit} /> },
                { key: 'credit', header: 'Credit', render: (row) => <MoneyDisplay value={row.credit} /> },
            ]} />
        </Panel>
        <Panel title="Ledger entries" className="mt-5">
            <DataTable rows={journal.ledger_entries ?? []} rowKey={(row) => row.id} columns={[
                { key: 'date', header: 'Date', render: (row) => formatDate(row.entry_date) },
                { key: 'account', header: 'Account', render: (row) => row.account ? `${row.account.code} - ${row.account.name}` : '-' },
                { key: 'debit', header: 'Debit', render: (row) => <MoneyDisplay value={row.debit} /> },
                { key: 'credit', header: 'Credit', render: (row) => <MoneyDisplay value={row.credit} /> },
                { key: 'balance', header: 'Balance after', render: (row) => <MoneyDisplay value={row.balance_after} /> },
            ]} />
        </Panel>

        {showReversal && <Panel title="Reverse journal" className="mt-5">
            <div className="grid gap-4 md:grid-cols-2">
                <Input label="Reversal date" type="date" value={reversalDate} onChange={(event) => setReversalDate(event.target.value)} />
                <Textarea label="Reason" value={reversalReason} onChange={(event) => setReversalReason(event.target.value)} />
            </div>
            <div className="mt-4 flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => setShowReversal(false)}>Keep journal</Button>
                <Button type="button" variant="danger" loading={submitting} disabled={!reversalDate || !reversalReason.trim()} onClick={() => void reverse()}>Create reversal</Button>
            </div>
        </Panel>}

        <ConfirmDialog
            open={action !== null}
            title={action === 'post' ? 'Post journal?' : 'Cancel draft?'}
            message={action === 'post'
                ? 'Posting creates immutable ledger entries. Corrections must use reversal.'
                : 'The draft will remain available for audit but cannot be posted.'}
            confirmLabel={action === 'post' ? 'Post journal' : 'Cancel draft'}
            danger={action !== 'post'}
            loading={submitting}
            onCancel={() => setAction(null)}
            onConfirm={() => void executeAction()}
        />
    </>;
}
