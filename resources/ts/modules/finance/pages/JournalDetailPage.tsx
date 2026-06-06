import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { DateDisplay, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge } from '../../../shared/components/erp/ErpUi';
import { journalApi } from '../services/journalApi';
import type { JournalEntry } from '../types/journal.types';

export function JournalDetailPage() {
    const { id } = useParams();
    const [entry, setEntry] = useState<JournalEntry | null>(null);
    const [error, setError] = useState('');
    useEffect(() => { if (!id) return; let active = true; void journalApi.get(Number(id)).then((response) => { if (active) setEntry(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load journal.'); }); return () => { active = false; }; }, [id]);
    if (!entry && !error) return <div className="flex justify-center p-16"><Spinner /></div>;
    if (!entry) return <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>;
    return <div className="mx-auto max-w-4xl space-y-5"><PageHeader actions={<SecondaryLink to="/finance/journals">Back</SecondaryLink>} eyebrow={entry.entryType} subtitle="Double-entry posting and source reference." title={entry.entryNumber} /><FormSection title="Journal summary"><dl className="grid gap-5 sm:grid-cols-3"><Info label="Date" value={<DateDisplay value={entry.entryDate} />} /><Info label="Debit" value={<MoneyDisplay value={entry.totalDebit} />} /><Info label="Credit" value={<MoneyDisplay value={entry.totalCredit} />} /><Info label="Status" value={<StatusBadge value={entry.status} />} /><Info label="Source" value={entry.sourceReference || entry.sourceModule || 'Manual'} /><Info label="Description" value={entry.description || 'Not provided'} /></dl></FormSection></div>;
}

function Info({ label, value }: { label: string; value: React.ReactNode }) { return <div><dt className="text-xs font-bold uppercase text-slate-400">{label}</dt><dd className="mt-1 font-semibold text-slate-800">{value}</dd></div>; }
