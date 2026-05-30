import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import {
    FinanceActivityTimeline,
    FinanceSourceReferencePanel,
    JournalEntryForm,
    JournalEntryLineTable,
    JournalPostingPreviewPanel,
    JournalStatusActionPanel,
} from '../components/FinanceComponents';
import { financeActivity, postingPreview } from '../mock/financeMock';
import { financeApi } from '../services/financeApi';
import type { JournalEntry } from '../types/finance.types';

export function JournalEntryListPage() {
    const [rows, setRows] = useState<JournalEntry[]>([]);

    useEffect(() => {
        financeApi.listJournalEntries().then((response) => setRows(response.data));
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/journal-entries/new"><Button>Create Journal</Button></Link>}
                eyebrow="Finance"
                subtitle="Generic journal workspace. Posting, reversing, period lock validation, and balance checks are backend-owned."
                title="Journal Entries"
            />
            <SearchFilterBar placeholder="Search journal number, source, reference, status..." />
            {rows.length ? (
                <div className="space-y-4">
                    {rows.map((journal) => (
                        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" key={journal.id}>
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <Link className="font-bold text-slate-950 hover:underline" to={`/finance/journal-entries/${journal.id}`}>{journal.journalNumber}</Link>
                                    <p className="mt-1 text-sm text-slate-500">{journal.description}</p>
                                    <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{journal.journalDate} / {journal.currency}</p>
                                </div>
                                <div className="flex gap-2">
                                    <Link to={`/finance/journal-entries/${journal.id}`}><Button variant="secondary">View</Button></Link>
                                    <Button variant="ghost">Post</Button>
                                    <Button variant="ghost">Reverse</Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : <EmptyState description="No journal entries returned yet." title="No journals" />}
        </div>
    );
}

export function JournalEntryCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Create journal input. Backend preview validates accounting impact before posting." title="Create Journal Entry" />
            <JournalEntryForm />
        </div>
    );
}

export function JournalEntryEditPage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Finance" subtitle="Edit draft journal input only. Posted journals stay immutable and must be reversed by backend workflow." title="Edit Journal Entry" />
            <JournalEntryForm />
        </div>
    );
}

export function JournalEntryDetailPage() {
    const { id = 'je-001' } = useParams();
    const [journal, setJournal] = useState<JournalEntry>();
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        financeApi.getJournalEntry(id).then((response) => setJournal(response.data));
    }, [id]);

    if (!journal) {
        return <EmptyState description="Loading journal entry..." title="Loading" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Button variant="secondary">Reverse</Button><Button variant="blue">Post</Button></>}
                eyebrow="Finance"
                subtitle="Journal detail. Backend owns debit/credit totals, period validation, posting, and reversal."
                title={journal.journalNumber}
            />
            <PreviewPanel
                rows={[
                    { label: 'Date', value: journal.journalDate },
                    { label: 'Currency', value: journal.currency },
                    { label: 'Status', value: journal.status },
                    { label: 'Reference', value: journal.reference ?? 'Manual' },
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
                    { label: 'Reversal', value: 'reversal' },
                    { label: 'Audit / History', value: 'audit' },
                ]}
                onChange={setActiveTab}
            />
            {activeTab === 'overview' ? <PreviewPanel rows={[{ label: 'Description', value: journal.description }, { label: 'Source', value: journal.sourceReference ?? 'Manual' }]} title="Overview" /> : null}
            {activeTab === 'lines' ? <JournalEntryLineTable rows={journal.lines} /> : null}
            {activeTab === 'posting' ? <JournalPostingPreviewPanel preview={postingPreview} /> : null}
            {activeTab === 'source' ? <FinanceSourceReferencePanel sourceModule={journal.sourceModule} sourceReference={journal.sourceReference} /> : null}
            {activeTab === 'reversal' ? <JournalStatusActionPanel journalId={journal.id} status={journal.status} /> : null}
            {activeTab === 'audit' ? <FinanceActivityTimeline rows={financeActivity} /> : null}
        </div>
    );
}
