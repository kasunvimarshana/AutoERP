import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataToolbar, type DataToolbarFilterValue } from '../../../shared/components/data/DataToolbar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    ApiErrorBanner,
    FinanceSourceReferencePanel,
    JournalEntryForm,
    JournalEntryLineTable,
    JournalPostingPreviewPanel,
    JournalStatusActionPanel,
    apiFieldErrors,
} from '../components/FinanceComponents';
import { financeApi } from '../services/financeApi';
import type { Account, FinancePostingPreview, JournalEntry, JournalEntryFormValues } from '../types/finance.types';

export function JournalEntryListPage() {
    const [rows, setRows] = useState<JournalEntry[]>([]);
    const [search, setSearch] = useState('');
    const [filters, setFilters] = useState<Record<string, DataToolbarFilterValue>>({});
    const [error, setError] = useState<Error | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setLoading(true);
        financeApi.listJournalEntries({
            search,
            status: String(filters.status ?? ''),
            type: String(filters.entry_type ?? ''),
        })
            .then((response) => {
                setRows(response.data);
                setError(null);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setLoading(false));
    }, [filters.entry_type, filters.status, search]);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/journal-entries/new"><Button>Create Journal</Button></Link>}
                eyebrow="Finance"
                subtitle="Generic journal workspace. Posting, reversing, period validation, and balance checks are backend-owned."
                title="Journal Entries"
            />
            <ApiErrorBanner error={error} />
            <DataToolbar
                disabled={loading}
                filterValues={filters}
                filters={[
                    { id: 'status', label: 'Status', type: 'status', options: ['draft', 'posted', 'reversed', 'voided'].map((value) => ({ label: value, value })) },
                    { id: 'entry_type', label: 'Entry type', type: 'select', options: ['MANUAL', 'ADJUSTMENT', 'OPENING', 'CLOSING'].map((value) => ({ label: value, value })) },
                ]}
                isLoading={loading}
                onFilterChange={(id, value) => setFilters((current) => ({ ...current, [id]: value }))}
                onResetFilters={() => setFilters({})}
                onSearchChange={setSearch}
                savedViewsDisabledReason="Saved views are not backed by a Finance preferences endpoint yet."
                searchPlaceholder="Search journal number, source, reference, status..."
                searchValue={search}
            />
            {rows.length ? (
                <div className="space-y-4">
                    {rows.map((journal) => (
                        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" key={journal.id}>
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <Link className="font-bold text-slate-950 hover:underline" to={`/finance/journal-entries/${journal.id}`}>{journal.journalNumber}</Link>
                                    <p className="mt-1 text-sm text-slate-500">{journal.description || 'No description'}</p>
                                    <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{journal.journalDate} / {journal.currency}</p>
                                </div>
                                <div className="flex gap-2">
                                    <Link to={`/finance/journal-entries/${journal.id}`}><Button variant="secondary">View</Button></Link>
                                    <Link to={`/finance/journal-entries/${journal.id}/edit`}><Button disabled={journal.status !== 'draft'} title={journal.status === 'draft' ? undefined : 'Only draft journals can be edited.'} variant="ghost">Edit</Button></Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : <EmptyState description="No journal entries returned for the current filters." title="No journals" />}
        </div>
    );
}

export function JournalEntryCreatePage() {
    return <JournalFormShell mode="create" />;
}

export function JournalEntryEditPage() {
    return <JournalFormShell mode="edit" />;
}

export function JournalEntryDetailPage() {
    const { id = '' } = useParams();
    const [journal, setJournal] = useState<JournalEntry>();
    const [preview, setPreview] = useState<FinancePostingPreview>();
    const [activeTab, setActiveTab] = useState('overview');
    const [error, setError] = useState<Error | null>(null);

    function load(): void {
        financeApi.getJournalEntry(id)
            .then((response) => {
                setJournal(response.data);
                setError(null);
            })
            .catch((caught: Error) => setError(caught));
    }

    useEffect(load, [id]);

    function previewPosting(): void {
        financeApi.previewJournalPosting(id, {})
            .then(setPreview)
            .catch((caught: Error) => setError(caught));
    }

    function postJournal(): void {
        financeApi.postJournalEntry(id)
            .then(load)
            .catch((caught: Error) => setError(caught));
    }

    function reverseJournal(): void {
        financeApi.reverseJournalEntry(id)
            .then(load)
            .catch((caught: Error) => setError(caught));
    }

    if (!journal) {
        return <EmptyState description="Loading journal entry..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to={`/finance/journal-entries/${journal.id}/edit`}><Button disabled={journal.status !== 'draft'} variant="secondary">Edit Draft</Button></Link>}
                eyebrow="Finance"
                subtitle="Journal detail. Backend owns debit/credit totals, period validation, posting, and reversal."
                title={journal.journalNumber}
            />
            <ApiErrorBanner error={error} />
            <PreviewPanel
                rows={[
                    { label: 'Date', value: journal.journalDate },
                    { label: 'Currency', value: journal.currency },
                    { label: 'Status', value: journal.status },
                    { label: 'Reference', value: journal.reference || 'Manual' },
                    { label: 'Backend debit total', value: journal.totalDebit },
                    { label: 'Backend credit total', value: journal.totalCredit },
                ]}
                status={journal.status}
                title="Journal Overview"
            />
            <Tabs
                active={activeTab}
                items={[
                    { label: 'Overview', value: 'overview' },
                    { label: 'Lines', value: 'lines' },
                    { label: 'Posting Preview', value: 'posting' },
                    { label: 'Source Reference', value: 'source' },
                    { label: 'Actions', value: 'actions' },
                    { label: 'Audit / History', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Description', value: journal.description || 'Not set' }, { label: 'Source', value: journal.sourceReference || 'Manual' }]} title="Overview" /> : null}
            {activeTab === 'lines' ? <JournalEntryLineTable rows={journal.lines} /> : null}
            {activeTab === 'posting' ? <JournalPostingPreviewPanel preview={preview} /> : null}
            {activeTab === 'source' ? <FinanceSourceReferencePanel sourceModule={journal.sourceModule} sourceReference={journal.sourceReference} sourceType={journal.sourceType} /> : null}
            {activeTab === 'actions' ? <JournalStatusActionPanel journal={journal} onPost={postJournal} onPreview={previewPosting} onReverse={reverseJournal} /> : null}
            {activeTab === 'audit' ? <EmptyState description="No Finance audit endpoint is currently exposed for journal entries." title="No audit read model" /> : null}
        </div>
    );
}

function JournalFormShell({ mode }: { mode: 'create' | 'edit' }) {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const [accounts, setAccounts] = useState<Account[]>([]);
    const [journal, setJournal] = useState<JournalEntry>();
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<Error | null>(null);

    useEffect(() => {
        financeApi.listAccounts({ is_active: true, per_page: 200 })
            .then((response) => setAccounts(response.data.filter((account) => account.allowsManualPosting)))
            .catch((caught: Error) => setError(caught));

        if (mode === 'edit') {
            financeApi.getJournalEntry(id).then((response) => setJournal(response.data)).catch((caught: Error) => setError(caught));
        }
    }, [id, mode]);

    function submit(values: JournalEntryFormValues): void {
        setSaving(true);
        const request = mode === 'edit' ? financeApi.updateJournalEntry(id, values) : financeApi.createJournalEntry(values);
        request
            .then((response) => {
                const createdId = String((response.data as { id?: string | number }).id ?? id);
                navigate(`/finance/journal-entries/${createdId}`);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setSaving(false));
    }

    const initialValues = useMemo(() => journalToForm(journal), [journal]);

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Finance"
                subtitle={mode === 'edit' ? 'Edit draft journal input only. Posted journals stay immutable and must be reversed.' : 'Create journal input. Backend preview validates accounting impact before posting.'}
                title={mode === 'edit' ? 'Edit Journal Entry' : 'Create Journal Entry'}
            />
            <ApiErrorBanner error={error} />
            {mode === 'edit' && !journal ? (
                <EmptyState description="Loading journal from backend..." title="Loading" />
            ) : (
                <JournalEntryForm accounts={accounts} errors={apiFieldErrors(error)} initialValues={initialValues} isSaving={saving} mode={mode} onSubmit={submit} />
            )}
        </div>
    );
}

function journalToForm(journal?: JournalEntry): Partial<JournalEntryFormValues> {
    if (!journal) {
        return {};
    }

    return {
        description: journal.description,
        entryType: journal.entryType,
        journalDate: journal.journalDate,
        journalNumber: journal.journalNumber,
        lines: journal.lines.map((line) => ({
            accountId: line.accountId ?? '',
            credit: line.credit === '0.0000' ? '' : line.credit,
            debit: line.debit === '0.0000' ? '' : line.debit,
            description: line.description,
        })),
        sourceModule: journal.sourceModule ?? '',
        sourceReference: journal.sourceReference ?? '',
        sourceType: journal.sourceType ?? '',
        status: journal.status,
    };
}
